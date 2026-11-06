<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditTrail;
use App\Models\BidangAllocation;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\KrafBidang;
use App\Models\Plot;
use App\Models\PlotSize;
use App\Models\Poll;
use App\Services\PlotAllocationAlgorithm;
use App\Support\EventVisibility;
use App\Support\PollingGuard;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * The lot draw: its plan, the plots built from it, and the stages it moves
 * through.
 *
 * WRITES ARE GATED IN routes/web.php (`can:manage-events`); index, show and
 * plots-by-size stay open to every role and narrow themselves, exactly as the
 * events screens do. A state admin needs to read the draw their companies are in.
 */
class PollingController extends Controller
{
    /**
     * A draw runs between two dates, and every screen that shows one reads them.
     * Held once so create and edit cannot drift into disagreeing about them.
     */
    private const POLLING_WINDOW_RULES = [
        'polling_start_date' => 'required|date',
        'polling_deadline' => 'required|date|after:polling_start_date',
    ];

    private const POLLING_WINDOW_MESSAGES = [
        'polling_start_date.required' => 'A Polling Start Date is required.',
        'polling_deadline.required' => 'A Polling End Date is required.',
        'polling_deadline.after' => 'The Polling End Date must fall after the Polling Start Date.',
    ];

    /** The plan's own rules. Create and edit post the same form. */
    private const PLAN_RULES = [
        'plot_sizes' => 'required|array|min:1',
        // A row is a LOCATION holding one or more size lines — "B: 20 × 3x3,
        // 10 × 3x6". Every plot has a size, because a lot is drawn only at a
        // registration's own booth size: an unsized plot is one nobody can win.
        'plot_sizes.*.sizes' => 'required|array|min:1',
        'plot_sizes.*.sizes.*.size' => 'required|string|max:50',
        'plot_sizes.*.sizes.*.quantity' => 'required|integer|min:1',
        // The plot code prefix — "HALL A-TBP1". Required, so no plot falls back
        // to a meaningless "LOC-TBP1".
        'plot_sizes.*.location' => 'required|string|max:50',
        'bidang_allocations' => 'nullable|array',
        'bidang_allocations.*.bidang_id' => 'required_with:bidang_allocations|integer',
        'bidang_allocations.*.plot_size_index' => 'required_with:bidang_allocations|integer|min:0',
        'bidang_allocations.*.size' => 'nullable|string|max:50',
        'bidang_allocations.*.plots_count' => 'required_with:bidang_allocations|integer|min:1',
    ];

    /** Where page one's settings wait while page two is filled in. */
    private const DRAFT_KEY = 'polling.draft';

    public function index(Request $request)
    {
        $admin = $request->user('admin');

        $events = Event::query()
            ->whereHas('plotSizes')
            ->with(['plotSizes', 'bidangAllocations'])
            ->withExists(['plots as has_assigned_plots' => fn ($q) => $q->where('is_allocated', true)])
            ->when($request->input('search'), fn ($q, $search) => $q->where('title', 'like', "%{$search}%"))
            ->when($request->input('polling_status'), fn ($q, $status) => $q->where('polling_status', $status))
            // The same rule EventVisibility applies to one event: a state admin
            // sees every event that is not a draft.
            ->unless($admin->hasGlobalAccess(), fn ($q) => $q->where('status', '!=', 'draft'))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.polling.index', [
            'events' => $events,
            'canManage' => $admin->canManageEvents(),
        ]);
    }

    /** Page 1 of 2 — when the draw runs. */
    public function create()
    {
        $events = Event::whereDoesntHave('plotSizes')
            ->where('polling_status', 'draft')
            ->latest()
            ->get();

        return view('admin.polling.create', [
            'events' => $events,
            'draft' => session(self::DRAFT_KEY, []),
        ]);
    }

    /**
     * Page 1 submit. Held in the session rather than written: a configuration
     * with no locations is one index() does not list, so the admin would be sent
     * back to a page their own half-finished draw is missing from.
     */
    public function storeSettings(Request $request)
    {
        $validated = $request->validate([
            'event_id' => 'required|exists:events,id',
        ] + self::POLLING_WINDOW_RULES, self::POLLING_WINDOW_MESSAGES);

        session([self::DRAFT_KEY => $validated + [
            'auto_polling_open' => $request->boolean('auto_polling_open'),
            'auto_polling_close' => $request->boolean('auto_polling_close'),
        ]]);

        return redirect()->route('admin.polling.create-plan');
    }

    /** Page 2 of 2 — what the draw holds. */
    public function createPlan()
    {
        $draft = session(self::DRAFT_KEY);

        if (! $draft) {
            return redirect()->route('admin.polling.create')
                ->withErrors(['error' => 'Start with the lot draw settings.']);
        }

        $event = Event::findOrFail($draft['event_id']);

        return view('admin.polling.create-plan', [
            'draft' => $draft,
            'event' => $event,
            'groupedBidangs' => $this->groupedBidangs(),
        ] + $this->drawSummary($event));
    }

    public function store(Request $request)
    {
        $draft = session(self::DRAFT_KEY, []);

        // Page one's answers fill in only what the request does not state, and
        // every rule below still runs over them.
        $request->merge(array_diff_key($draft, $request->all()));

        if (! $request->filled('event_id')) {
            return redirect()->route('admin.polling.create')
                ->withErrors(['error' => 'Start with the lot draw settings.']);
        }

        $validated = $request->validate([
            'event_id' => 'required|exists:events,id',
        ] + self::PLAN_RULES + self::POLLING_WINDOW_RULES, self::POLLING_WINDOW_MESSAGES);

        $this->validateBidangIds($validated['bidang_allocations'] ?? []);

        $event = Event::findOrFail($validated['event_id']);

        // A second store for the same event would build a second set of plots on
        // top of the first. The create list hides such events; this is the door.
        if ($event->plotSizes()->exists()) {
            return redirect()->route('admin.polling.edit', $event)
                ->withErrors(['error' => 'This event already has a lot plan. Edit it instead.']);
        }

        // savePlan() throws its over-allocation check after the sizes are written;
        // without the transaction a refused plan left them behind, and the event
        // fell off the create list where it could be retried.
        DB::transaction(function () use ($event, $validated, $request) {
            $event->update([
                // Always draft. The guards on the way to "polling" live in
                // changeStatus(), and this is not it.
                'polling_status' => 'draft',
                'polling_start_date' => Carbon::parse($validated['polling_start_date'], config('app.timezone')),
                'polling_deadline' => Carbon::parse($validated['polling_deadline'], config('app.timezone')),
                'auto_polling_open' => $request->boolean('auto_polling_open'),
                'auto_polling_close' => $request->boolean('auto_polling_close'),
            ]);

            $this->savePlan($event, $validated);
            $this->doGeneratePlots($event);
        });

        session()->forget(self::DRAFT_KEY);

        return redirect()->route('admin.polling.show', $event)
            ->with('success', 'Lot draw created.');
    }

    public function show(Request $request, Event $event)
    {
        $admin = $request->user('admin');

        // "May you open this event". The state filter below is a different
        // question — whose rows you see inside it — and both are needed.
        EventVisibility::authorize($event);

        $event->load([
            'plotSizes',
            'bidangAllocations.bidang',
            'bidangAllocations.plotSize',
            'plots.plotSize',
            'plots.bidang',
            'plots.company',
            'registrations.company',
            'registrations.plot',
        ]);

        // share_percent reads $allocation->event; hand over the one in hand
        // rather than a SELECT per allocation.
        $event->bidangAllocations->each->setRelation('event', $event);

        // A plot code ends in a counter, so text order runs A-TBP1, A-TBP10 …
        $event->setRelation('plots', $event->plots->sortBy('plot_code', SORT_NATURAL)->values());

        if (! $admin->hasGlobalAccess()) {
            $event->setRelation('registrations', $event->registrations
                ->filter(fn ($r) => $r->company?->state === $admin->state)->values());

            // Free plots stay visible — they are the inventory. A held plot shows
            // only when it is held by this admin's own zone.
            $event->setRelation('plots', $event->plots
                ->filter(fn ($p) => ! $p->is_allocated || $p->company?->state === $admin->state)->values());
        }

        $drawnAt = Poll::where('event_id', $event->id)->pluck('created_at', 'user_id');

        return view('admin.polling.show', [
            'event' => $event,
            'drawnAt' => $drawnAt,
            // Who can still be given a preset: in the draw, holding nothing.
            'unseated' => $event->registrations->whereNull('plot_id')->sortBy('company.name')->values(),
            'canManage' => $admin->canManageEvents(),
            // Named in the Start Polling prompt, so the gap is read at the moment
            // of the decision. The plan freezes on the first drawn lot.
            'codeGaps' => $this->codesWithoutPlots($event),
            'blocker' => $event->polling_status === 'draft' ? PollingGuard::blocker($event) : null,
        ]);
    }

    public function edit(Event $event)
    {
        $event->load(['plotSizes', 'bidangAllocations.plotSize']);

        return view('admin.polling.edit', [
            'event' => $event,
            'groupedBidangs' => $this->groupedBidangs(),
        ] + $this->drawSummary($event));
    }

    public function update(Request $request, Event $event)
    {
        // The form rewrites the whole plan, plots included.
        if ($this->planIsFrozen($event)) {
            return $this->frozenPlanError();
        }

        $validated = $request->validate(self::PLAN_RULES + self::POLLING_WINDOW_RULES, self::POLLING_WINDOW_MESSAGES);

        $this->validateBidangIds($validated['bidang_allocations'] ?? []);

        // Every plot goes before the rebuild; a failure part-way through would
        // leave the event with no plots and nothing to rebuild them from.
        DB::transaction(function () use ($event, $validated, $request) {
            // Editing the plan never moves the draw's stage — that is
            // changeStatus(), with its guards.
            $event->update([
                'polling_start_date' => Carbon::parse($validated['polling_start_date'], config('app.timezone')),
                'polling_deadline' => Carbon::parse($validated['polling_deadline'], config('app.timezone')),
                'auto_polling_open' => $request->boolean('auto_polling_open'),
                'auto_polling_close' => $request->boolean('auto_polling_close'),
            ]);

            // planIsFrozen() refuses any held plot, so no preset can reach here:
            // a reservation sets is_allocated. There is nothing to carry across.

            // Before the first statement that removes anything — see
            // recordPlanCascade().
            $audit = $this->recordPlanCascade($event, 'plan_rewrite');

            $this->savePlan($event, $validated);
            $this->settleSizes($audit, $event);
            $this->doGeneratePlots($event);
        });

        return redirect()->route('admin.polling.show', $event)
            ->with('success', 'Lot plan updated, and its plots rebuilt.');
    }

    /**
     * Clear the whole plan and return the draw to draft.
     *
     * The confirm() on the Clear button in admin/polling/show.blade.php is the
     * admin-facing sentence for this method. Every step below has to appear in
     * it.
     */
    public function destroyPolling(Event $event)
    {
        if (in_array($event->polling_status, ['polling', 'closed', 'completed'], true)) {
            return back()->withErrors(['error' => 'The lot plan cannot be cleared once voting has started.']);
        }

        // A preset is a real, held lot and can be made while the draw is still
        // draft. Every other writer of the plan refuses once one exists.
        if ($this->planIsFrozen($event)) {
            return $this->frozenPlanError();
        }

        DB::transaction(function () use ($event) {
            $this->recordPlanCascade($event, 'plan_cleared');

            // No registration points at a plot: a held plot freezes the plan,
            // and a preset is a held plot.
            $event->plots()->delete();
            $event->bidangAllocations()->delete();
            $event->presetAllocations()->delete();
            $event->plotSizes()->delete();

            $event->forceFill([
                'total_plots' => 0,
                'polling_status' => 'draft',
                'polling_start_date' => null,
                'polling_deadline' => null,
            ])->save();
        });

        return redirect()->route('admin.polling.index')
            ->with('success', 'Lot plan cleared. The event is back in draft.');
    }

    /**
     * Build every plot from the plan, once. Refused when plots already exist —
     * stricter than planIsFrozen() on purpose: an event with generated but undrawn
     * plots is still somebody's plan, and this would silently rebuild it.
     */
    public function generatePlots(Event $event)
    {
        if ($event->plots()->exists()) {
            return back()->withErrors(['error' => 'This event already has plots. Edit the lot plan instead.']);
        }

        if (! $event->plotSizes()->exists()) {
            return back()->withErrors(['error' => 'This event has no lot plan to build plots from.']);
        }

        DB::transaction(fn () => $this->doGeneratePlots($event));

        return back()->with('success', $event->plots()->count().' plots generated.');
    }

    public function changeStatus(Request $request, Event $event)
    {
        $validated = $request->validate([
            'polling_status' => ['required', Rule::in(array_keys(Event::POLLING_STATUSES))],
        ]);

        $from = $event->polling_status;
        $to = $validated['polling_status'];

        $allowed = [
            'draft' => ['polling', 'cancelled'],
            'polling' => ['closed', 'cancelled'],
            'closed' => ['completed', 'polling', 'cancelled'],
            'completed' => [],
            'cancelled' => ['draft'],
        ];

        if (! in_array($to, $allowed[$from] ?? [], true)) {
            return back()->withErrors(['error' => "Cannot change the draw from \"{$from}\" to \"{$to}\"."]);
        }

        $gaps = collect();

        if ($to === 'polling') {
            if ($blocker = PollingGuard::blocker($event)) {
                return back()->withErrors(['error' => $blocker]);
            }

            // The draw CAN finish. Whether it finishes well is a different
            // question, and this is the last moment anyone can still answer it.
            $gaps = $this->codesWithoutPlots($event);
        }

        // Completed means every lot is out. A closed draw with people still
        // unseated is the state "Assign remaining lots" exists for, and
        // "completed" has no way back.
        if ($to === 'completed' && $event->registrations()->whereNull('plot_id')->exists()) {
            return back()->withErrors(['error' => 'Some companies in the draw still have no lot. Assign the remaining lots first.']);
        }

        $event->update(['polling_status' => $to]);

        $label = Event::pollingStatusLabel($to);

        if ($gaps->isNotEmpty()) {
            $named = $gaps->map(fn ($n, $code) => "{$code} ({$n})")->implode(', ');

            return back()->with('warning',
                "The draw is now \"{$label}\". {$gaps->sum()} registration(s) hold a code with no plot of its own — {$named}. "
                .'They will draw from the uncoded plots instead, and the plan freezes as soon as the first lot is drawn.');
        }

        return back()->with('success', "The draw is now \"{$label}\".");
    }

    /** Everyone still without a lot, in one go — once voting has closed. */
    public function processResults(Event $event)
    {
        // The button only shows on a closed draw; this is the door behind it.
        // Without it a hand-posted request ran the bulk run over a draft, past
        // every guard on the way to "polling".
        if ($event->polling_status !== 'closed') {
            return back()->withErrors(['error' => 'Remaining lots can only be assigned once voting is closed.']);
        }

        $result = (new PlotAllocationAlgorithm)->run($event);

        if ($result['waiting_list']) {
            $names = collect($result['waiting_list']);

            return redirect()->route('admin.polling.show', $event)->with('warning',
                "{$result['allocated']} lot(s) assigned, but {$names->count()} could not be placed — "
                .$names->take(5)->implode(', ')
                .($names->count() > 5 ? ', and '.($names->count() - 5).' more' : '').'. '
                .'The draw stays closed, not completed, so their lots can still be sorted out.');
        }

        return redirect()->route('admin.polling.show', $event)
            ->with('success', "{$result['allocated']} lot(s) assigned. Every company in the draw now has a lot.");
    }


    /** Set a lot aside for one company before the draw. */
    public function presetAssign(Request $request, Event $event)
    {
        $data = $request->validate([
            'plot_id' => 'required|integer',
            'registration_id' => 'required|integer',
        ]);

        if ($event->polling_status === 'completed') {
            return back()->withErrors(['error' => 'This draw is completed.']);
        }

        // Both scoped to THIS event — a posted id from another event 404s.
        $plot = $event->plots()->where('is_allocated', false)->findOrFail($data['plot_id']);
        $registration = $event->registrations()->with('company')->findOrFail($data['registration_id']);

        if ($registration->plot_id) {
            return back()->withErrors(['error' => "{$registration->company->name} already has a lot."]);
        }

        // The reservation is held against the account, as a drawn lot is.
        if (! $registration->user_id) {
            return back()->withErrors(['error' => "{$registration->company->name} has no participant account yet."]);
        }

        // A preset may cross craft codes — that is what an override is for, and
        // the form asks first. The draw itself never pairs these, so leave a trace.
        $plotCode = strtoupper((string) $plot->bidang?->code);
        $regCode = strtoupper((string) $registration->code);

        if ($plotCode && $regCode && $plotCode !== $regCode) {
            Log::warning('Preset assignment crosses craft codes', [
                'event_id' => $event->id,
                'plot_code' => $plot->plot_code,
                'plot_craft_code' => $plotCode,
                'registration_id' => $registration->id,
                'company_code' => $regCode,
                'admin_id' => $request->user('admin')->id,
            ]);
        }

        // One act or neither. Half of it — plot held, registration still at
        // plot_id NULL — makes LotAllocator hand that company a SECOND lot.
        DB::transaction(function () use ($plot, $registration) {
            $plot->update([
                'company_id' => $registration->company_id,
                'allocated_to' => $registration->user_id,
                'is_allocated' => true,
                'allocation_type' => 'preset',
                'is_locked' => true,
                'allocated_at' => now(),
            ]);

            $registration->update(['plot_id' => $plot->id]);
        });

        return back()->with('success', "Plot {$plot->plot_code} set aside for {$registration->company->name}.");
    }

    /**
     * Take a lot back off whoever holds it.
     *
     * Two different acts behind one button. Undoing a reservation gives back
     * something nobody had drawn yet; undoing a DRAW destroys a confirmed result
     * and the Poll behind it, and lets that company draw again. So it says which
     * of the two it did, and leaves a trace for the one that threw something away.
     */
    public function unassignPlot(Request $request, Event $event, Plot $plot)
    {
        // {plot} binds against every event, not this one.
        abort_if($plot->event_id !== $event->id, 404);

        $wasDrawn = $plot->allocation_type !== 'preset'
            || Poll::where('event_id', $event->id)->where('user_id', $plot->allocated_to)->exists();

        DB::transaction(function () use ($request, $event, $plot, $wasDrawn) {
            $registration = $event->registrations()->where('plot_id', $plot->id)->first();

            if ($registration) {
                $registration->update([
                    'plot_id' => null,
                    'confirmed_participation' => false,
                    'confirmed_at' => null,
                ]);

                // "Has drawn already" is read from Poll; it has to go too, or the
                // company can never draw again.
                Poll::where('event_id', $event->id)->where('user_id', $registration->user_id)->delete();

                if ($wasDrawn) {
                    Log::warning('A drawn lot was unassigned by an admin', [
                        'event_id' => $event->id,
                        'plot_code' => $plot->plot_code,
                        'registration_id' => $registration->id,
                        'company_id' => $registration->company_id,
                        'admin_id' => $request->user('admin')->id,
                    ]);
                }
            }

            $plot->update([
                'company_id' => null,
                'allocated_to' => null,
                'is_allocated' => false,
                'allocation_type' => 'algorithm',
                'is_locked' => false,
                'allocated_at' => null,
            ]);
        });

        return back()->with('success', $wasDrawn
            ? "Plot {$plot->plot_code} was drawn, not reserved — the result has been cleared, and that company can draw again."
            : "Reservation on plot {$plot->plot_code} removed.");
    }

    public function viewPlotsBySize(Request $request, Event $event, PlotSize $plotSize)
    {
        abort_if($plotSize->event_id !== $event->id, 404);

        // Its own URL, never routed through show(), so it needs the gate in its
        // own right or it is the back door into a screen show() refuses.
        EventVisibility::authorize($event);

        $admin = $request->user('admin');

        $plots = $event->plots()
            ->where('plot_size_id', $plotSize->id)
            ->with(['bidang', 'company'])
            ->get()
            ->sortBy('plot_code', SORT_NATURAL)
            ->values();

        // A state admin still sees the true counts, but not WHO holds another
        // zone's lot — redacted rather than dropped, or the counts would lie.
        if (! $admin->hasGlobalAccess()) {
            $plots->each(function ($p) use ($admin) {
                if ($p->is_allocated && $p->company?->state !== $admin->state) {
                    $p->setRelation('company', null);
                }
            });
        }

        return view('admin.polling.plots-by-size', compact('event', 'plotSize', 'plots'));
    }

    /* ── The plan ────────────────────────────────────────────────────────── */

    /**
     * The plan is free to be revised until the first lot is held — that is what
     * the draft stage is for. After that a rewrite would take live allocations
     * with it. The line is the first HELD plot, preset included, not the first
     * plot: before anyone holds one, regenerating is how a mistake gets fixed.
     */
    private function planIsFrozen(Event $event): bool
    {
        return $event->plots()->where('is_allocated', true)->exists();
    }

    private function frozenPlanError(): RedirectResponse
    {
        return back()->withErrors(['error' =>
            'Lots are already held in this draw, so its plan is locked. '
            .'Remove those lots first if the plan genuinely has to change.',
        ]);
    }

    /**
     * Record what rewriting or clearing a plan destroys.
     *
     * Both work through mass deletes, which fire no model events, and through
     * the plot_sizes cascade underneath — so without this the plots go unlogged.
     * Called at the TOP of the act: by the time savePlan() drops a stale size the
     * plots are already gone, and a count there would report nothing destroyed.
     *
     * Counted on plot_size_id, which is complete: all three columns are NOT NULL.
     * The action is not `deleted` — the event survives. AuditTrail recognises a
     * summary by its `cascade_deleted` key. A rewrite pairs with the
     * `plots_generated` row doGeneratePlots() writes at the end of the same act.
     *
     * Returned, because a REWRITE keeps every (location, size) pair the new plan
     * still names: at the top of the act all sizes look doomed, and only after
     * savePlan() is it known which went. settleSizes() corrects the one count.
     */
    private function recordPlanCascade(Event $event, string $action): ?AuditTrail
    {
        $sizeIds = $event->plotSizes()->pluck('id');

        if ($sizeIds->isEmpty()) {
            return null;
        }

        $under = fn (string $table) => DB::table($table)->whereIn('plot_size_id', $sizeIds)->count();

        return AuditTrail::record($action, $event, ['plot_sizes' => $sizeIds->values()->all()], [
            'cascade_deleted' => [
                'plot_sizes' => $sizeIds->count(),
                'plots' => $under('plots'),
                'bidang_allocations' => $under('bidang_allocations'),
                'preset_allocations' => $under('preset_allocations'),
            ],
            'links_cleared' => [],
        ]);
    }

    /** After a rewrite: the sizes that are really gone, out of those recorded. */
    private function settleSizes(?AuditTrail $row, Event $event): void
    {
        if (! $row) {
            return;
        }

        $gone = collect($row->old_values['plot_sizes'])->diff($event->plotSizes()->pluck('id'));

        $summary = $row->new_values;
        $summary['cascade_deleted']['plot_sizes'] = $gone->count();
        $row->forceFill(['old_values' => ['plot_sizes' => $gone->values()->all()], 'new_values' => $summary])->save();
    }

    /**
     * Write a submitted plan onto the event, ready for doGeneratePlots().
     *
     * A plot_sizes row is a (location, size) pair — what a plot carries and what
     * a registration's booth size is matched against. The form types exactly
     * that: a LOCATION card with one line per size it holds, and allocations
     * under it, each at one of those sizes. Pairs are matched on (location,
     * size) so a surviving pair keeps its id.
     */
    private function savePlan(Event $event, array $validated): void
    {
        $locations = array_values($validated['plot_sizes']);
        $allocations = $validated['bidang_allocations'] ?? [];

        // One card per place: a second card with the same name would have its
        // allocations land on the first card's rows when the form is reopened.
        // A place with two sizes is one card with two size lines.
        $names = array_map(fn ($l) => trim((string) $l['location']), $locations);
        $twice = array_diff_assoc($names, array_unique($names));

        if ($twice !== []) {
            $name = reset($twice);

            throw ValidationException::withMessages([
                'plot_sizes' => "Location '{$name}' is on two cards. Keep one card for '{$name}' and add each booth size to it as a line.",
            ]);
        }

        // location index => [size => plots it holds]. A size typed twice on one
        // card is the same pair, so its lines add up.
        $lines = [];

        foreach ($locations as $index => $location) {
            foreach ($location['sizes'] as $line) {
                $size = trim((string) $line['size']);
                $lines[$index][$size] = ($lines[$index][$size] ?? 0) + (int) $line['quantity'];
            }
        }

        // location index => [size => plots allocated], summed across the whole
        // submission: a row-at-a-time check misses two rows that only overflow
        // together.
        $wanted = [];

        foreach ($allocations as $alloc) {
            $index = $alloc['plot_size_index'];

            // A position, not an id — a stale tab can point past the end.
            if (! isset($locations[$index])) {
                throw ValidationException::withMessages([
                    'bidang_allocations' => "An allocation points at location #{$index}, which this plan does not have. Reload the page and try again.",
                ]);
            }

            $size = $this->sizeOf($alloc, $lines[$index]);
            $name = $locations[$index]['location'];

            if (! isset($lines[$index][$size])) {
                throw ValidationException::withMessages([
                    'bidang_allocations' => "Location '{$name}' has no {$size} plots. Add a {$size} line to it, or give the allocation one of its sizes.",
                ]);
            }

            $wanted[$index][$size] = ($wanted[$index][$size] ?? 0) + $alloc['plots_count'];
        }

        foreach ($wanted as $index => $sizes) {
            foreach ($sizes as $size => $total) {
                if ($total > $lines[$index][$size]) {
                    throw ValidationException::withMessages([
                        'bidang_allocations' => "Location '{$locations[$index]['location']}' holds {$lines[$index][$size]} plots of {$size}, but {$total} are allocated.",
                    ]);
                }
            }
        }

        $keptSizes = [];
        $rowFor = [];   // "locationIndex|size" => plot_sizes.id

        $existing = $event->plotSizes()->get()->keyBy(fn ($r) => $r->location.'|'.$r->size);

        foreach ($locations as $index => $location) {
            // Each line becomes a row. Its plots no allocation claims are built
            // uncoded — the open pool at that size — by doGeneratePlots().
            foreach ($lines[$index] as $size => $quantity) {
                $pairKey = $location['location'].'|'.$size;

                $row = $existing[$pairKey] ?? $event->plotSizes()->make([
                    'location' => $location['location'],
                    'size' => (string) $size,
                ]);
                $row->quantity = $quantity;
                $row->save();

                $existing[$pairKey] = $row;
                $keptSizes[] = $row->id;
                $rowFor[$index.'|'.$size] = $row->id;
            }
        }

        // Plots first: plots.plot_size_id would cascade them anyway, but doing it
        // by hand keeps the order obvious. Audited by recordPlanCascade(), above.
        $event->plots()->delete();
        $event->bidangAllocations()->delete();
        $event->plotSizes()->whereNotIn('id', $keptSizes)->delete();

        // Counted, never taken from the form — the sum of the locations has no
        // second source to disagree with.
        $event->forceFill(['total_plots' => (int) $event->plotSizes()->sum('quantity')])->save();

        // Always new rows. NEVER reuse a posted allocation id: BidangAllocation::
        // find() is unscoped, so an id from another event would be overwritten
        // with this one's — a cross-event write through the request body.
        foreach ($allocations as $alloc) {
            $index = $alloc['plot_size_index'];

            BidangAllocation::create([
                'event_id' => $event->id,
                'bidang_id' => $alloc['bidang_id'],
                'plot_size_id' => $rowFor[$index.'|'.$this->sizeOf($alloc, $lines[$index])],
                'plots_count' => $alloc['plots_count'],
            ]);
        }
    }

    /**
     * Every craft code posted in the allocations, checked once for the array —
     * and checked to be a CODE. `kraf_bidangs` holds two lists, and a category
     * row (code IS NULL) here would build plots no registration can ever match.
     */
    private function validateBidangIds(array $allocations): void
    {
        $ids = array_values(array_unique(array_filter(array_column($allocations, 'bidang_id'))));

        if (! $ids || KrafBidang::codes()->whereIn('id', $ids)->count() === count($ids)) {
            return;
        }

        throw ValidationException::withMessages([
            'bidang_allocations' => 'A craft code in this plan is not on the coding sheet. Reload the page and build it again.',
        ]);
    }

    /**
     * The booth size an allocation is for: its own, else its location's only
     * size when the location has just one.
     *
     * @param  array<string, int>  $sizes  the location's lines, size => plots
     */
    private function sizeOf(array $allocation, array $sizes): string
    {
        $own = trim((string) ($allocation['size'] ?? ''));

        return $own !== '' || count($sizes) !== 1 ? $own : (string) array_key_first($sizes);
    }

    /**
     * Build the event's plots from its plan, in chunked inserts: a hall is
     * bounded only by what an admin typed, and two statements per plot (the row
     * and its audit row) held the transaction open long enough to time out.
     *
     * Plot::insert() fires no model events, so one summary row against the event
     * stands in for the per-plot audit rows. Every LATER change to a plot still
     * audits normally, through the model.
     */
    private function doGeneratePlots(Event $event): void
    {
        $event->plots()->delete();

        $counters = [];
        $rows = [];
        $now = now();

        $allocations = $event->bidangAllocations()->with('bidang')->get();

        foreach ($event->plotSizes()->get() as $plotSize) {
            $location = $plotSize->location ?: 'LOC';
            $forSize = $allocations->where('plot_size_id', $plotSize->id);

            // A size can be allocated in part; what is left is built uncoded.
            $batches = $forSize->map(fn ($a) => [$a->bidang_id, $a->bidang->code, $a->plots_count])->values()->all();

            $unallocated = $plotSize->quantity - $forSize->sum('plots_count');
            if ($unallocated > 0) {
                // An uncoded plot reads "HALL A-3X34": its size, not a category.
                $batches[] = [null, $plotSize->size, $unallocated];
            }

            foreach ($batches as [$bidangId, $token, $count]) {
                $key = $location.'|'.$token;
                $counters[$key] ??= 1;

                for ($i = 0; $i < $count; $i++) {
                    $rows[] = [
                        'event_id' => $event->id,
                        'plot_size_id' => $plotSize->id,
                        'company_id' => null,
                        'allocated_to' => null,
                        'bidang_id' => $bidangId,
                        'sub_bidang_id' => null,
                        'plot_code' => Plot::codeFor($location, $token, $counters[$key]++),
                        'is_allocated' => false,
                        'allocation_type' => 'algorithm',
                        'is_locked' => false,
                        'allocated_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        // 500 at a time: MySQL's placeholder limit is the ceiling.
        foreach (array_chunk($rows, 500) as $chunk) {
            Plot::insert($chunk);
        }

        AuditTrail::record('plots_generated', $event, null, [
            'plots' => count($rows),
            // "DEWAN A|RKP" => 12 — the counter holds the NEXT number.
            'per_code' => collect($counters)->map(fn ($next) => $next - 1)->all(),
        ]);
    }

    /* ── What the plan is measured against ───────────────────────────────── */

    /**
     * Registrations whose craft code has no plot of its own, by code. Not a
     * blocker — the open pool absorbs them — but a draw that finishes badly, and
     * worth saying before the plan freezes. Anyone holding a lot is not waiting.
     *
     * @return Collection<string, int> code => count
     */
    private function codesWithoutPlots(Event $event): Collection
    {
        $plotCodes = $event->plots()
            ->join('kraf_bidangs', 'plots.bidang_id', '=', 'kraf_bidangs.id')
            ->distinct()
            ->pluck('kraf_bidangs.code')
            ->filter();

        return $event->registrations()
            ->whereNull('plot_id')
            ->get()
            ->reject(fn ($reg) => filled($reg->code) && $plotCodes->contains($reg->code))
            ->groupBy(fn ($reg) => $reg->code ?: 'No code')
            ->map->count()
            ->sortDesc();
    }

    /**
     * The reference card above the plan: how many are in the draw, and what they
     * ask for by craft code AND booth size, each with the plots that match.
     *
     * Demand alone was the whole problem in `daftar`: a code read "· 11" in the
     * style of the covered ones while eighteen people lost their category. So
     * every chip carries both numbers.
     *
     * A registration's code is a string; the form selects code ROWS by id. The
     * chip carries the row's id so the form's script can pair the two.
     *
     * @return array{drawCount:int, drawByBidang:Collection, hasPlots:bool}
     */
    private function drawSummary(Event $event): array
    {
        $entries = $event->registrations()->get();
        $codeRows = KrafBidang::codes()->get()->keyBy('code');

        // Plots, not allocations — plots are what LotAllocator draws from.
        $supply = $event->plots()->whereNotNull('plots.bidang_id')
            ->join('plot_sizes', 'plot_sizes.id', '=', 'plots.plot_size_id')
            ->selectRaw('plots.bidang_id as bidang_id, plot_sizes.size as size, count(*) as n')
            ->groupBy('plots.bidang_id', 'plot_sizes.size')
            ->get();

        $supplyBySize = $supply->mapWithKeys(fn ($r) => [$r->bidang_id.'|'.$r->size => (int) $r->n]);
        $supplyByCode = $supply->groupBy('bidang_id')->map(fn ($rows) => (int) $rows->sum('n'));

        $rowOf = fn ($r) => filled($r->code) ? $codeRows->get($r->code) : null;

        $byBidang = $entries
            ->groupBy(fn ($r) => $rowOf($r)?->name ?? 'No code')
            ->map(fn ($rows) => $rows
                ->groupBy(function ($r) use ($rowOf) {
                    // No known code: nothing to size against — it draws from the
                    // open pool whatever it asked for.
                    if (! $rowOf($r)) {
                        return '—';
                    }

                    return $r->plot_size ? "{$r->code} {$r->plot_size}" : "{$r->code} (any)";
                })
                ->map(function ($group) use ($rowOf, $supplyBySize, $supplyByCode) {
                    $id = $rowOf($group->first())?->id;
                    $size = $group->first()->plot_size;

                    return [
                        'bidang_id' => $id,
                        'size' => $size,
                        'needed' => $group->count(),
                        'have' => $id === null ? 0 : ($size
                            ? ($supplyBySize[$id.'|'.$size] ?? 0)
                            : ($supplyByCode[$id] ?? 0)),
                    ];
                })
                ->sortKeysUsing(fn ($a, $b) => strnatcasecmp($a, $b)))
            // Ordered as the coding sheet is printed: by each group's lowest bil.
            ->sortBy(fn ($codes, $name) => $codeRows->where('name', $name)->min('bil') ?? PHP_INT_MAX);

        return [
            'drawCount' => $entries->count(),
            'drawByBidang' => $byBidang,
            'hasPlots' => $supply->isNotEmpty(),
        ];
    }

    /**
     * The craft codes an allocation can point at, grouped by their category name
     * for the two-step Category → Code dropdown. Codes only: a category row
     * matches no registration, so it is a heading here, never a choice.
     */
    private function groupedBidangs(): Collection
    {
        return KrafBidang::codes()
            ->active()
            ->orderBy('bil')
            ->orderBy('code')
            ->get()
            ->groupBy('name')
            ->map(fn ($group) => $group->map(fn ($bidang) => [
                'id' => $bidang->id,
                'code' => $bidang->code,
                'name' => $bidang->name,
                'pecahan_bidang' => $bidang->pecahan_bidang ?? $bidang->name,
            ]));
    }
}
