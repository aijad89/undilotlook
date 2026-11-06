<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Support\EventVisibility;
use App\Support\KrafPicker;
use App\Support\PlotSizes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * The draw list: which companies are in one event's lot draw.
 *
 * PUTTING A COMPANY HERE IS THE APPROVAL. There is no status to set and nothing
 * to approve afterwards — the row exists, so the company is in the draw. What
 * the office still has to say is the craft CODE it draws under and the booth
 * SIZE it needs, because the plan is built from those two numbers.
 *
 * THE CODE IS A STRING, NOT A REFERENCE, and that is deliberate: the allocator
 * compares `event_registrations.code` against the code on the `kraf_bidangs` row
 * a plot is categorised by. The dropdown offers the active codes; the column
 * stores what was picked.
 *
 * SCOPE IS DOUBLE HERE, and both halves are needed. EventVisibility::authorize()
 * answers "may you open this event at all"; the query below answers "whose rows
 * may you see inside it". Dropping either leaves a hole the other does not cover.
 */
class EntRegistrationController extends Controller
{
    /** How many removed rows an undo may hold. One bulk operation's worth. */
    private const UNDO_LIMIT = 200;

    public function index(Request $request, Event $event)
    {
        EventVisibility::authorize($event);

        $admin = $request->user('admin');
        $scoped = fn ($query) => $query->unless(
            $admin->hasGlobalAccess(),
            fn ($q) => $q->whereHas('company', fn ($c) => $c->where('state', $admin->state))
        );

        $registrations = $scoped($event->registrations()->with(['company.user', 'plot']))
            ->when($request->input('code'), fn ($q, $code) => $q->where('code', $code))
            ->when($request->input('search'), fn ($q, $search) => $q->whereHas(
                'company', fn ($c) => $c->where('name', 'like', "%{$search}%")
            ))
            ->join('companies', 'companies.id', '=', 'event_registrations.company_id')
            ->orderBy('companies.name')
            ->select('event_registrations.*')
            ->paginate(25)
            ->withQueryString();

        // A set-aside lot stays a surprise to the zone until it is confirmed.
        if (! $admin->hasGlobalAccess()) {
            \App\Models\Plot::maskPresets($registrations->pluck('plot'));
            $registrations->each(fn ($r) => $r->plot && ! $r->plot->is_allocated && $r->setRelation('plot', null));
        }

        // Companies that could still be added: in this admin's zone, on the
        // books, and not already in this draw.
        $candidates = Company::query()
            ->active()
            ->unless($admin->hasGlobalAccess(), fn ($q) => $q->where('state', $admin->state))
            ->whereDoesntHave('eventRegistrations', fn ($q) => $q->where('event_id', $event->id))
            ->orderBy('name')
            ->get(['id', 'name', 'state', 'kraf_code', 'plot_size']);

        return view('admin.events.registrations.index', [
            'event' => $event,
            'registrations' => $registrations,
            'candidates' => $candidates,
            'codes' => KrafPicker::codes(),
            'plotSizes' => PlotSizes::all(),
            'undoCount' => count(session('draw_list_undo', [])),
        ]);
    }

    /**
     * Add one or more companies to the draw list.
     *
     * `user_id` is copied from the company at this point because the draw keys on
     * the ACCOUNT — polls, preset allocations and `plots.allocated_to` all do —
     * while ownership stays `company_id`. Two keys, two questions.
     */
    public function store(Request $request, Event $event)
    {
        EventVisibility::authorize($event);

        $data = $request->validate([
            'company_id' => ['required', 'array', 'min:1'],
            'company_id.*' => ['integer'],
            'code' => ['nullable', 'string', 'max:50'],
            'plot_size' => ['nullable', Rule::in(PlotSizes::all())],
        ]);

        // Re-read the companies through the same scope the picker used, so a
        // typed id from another zone is not added by posting it directly.
        $companies = $this->scopedCompanies($request, $data['company_id']);

        if ($companies->isEmpty()) {
            return back()->with('error', 'No company from your zone matched that.');
        }

        $added = 0;

        DB::transaction(function () use ($companies, $event, $data, &$added) {
            foreach ($companies as $company) {
                // A company cannot be in the same draw twice — there is a unique
                // index saying so. firstOrCreate makes a double submit harmless
                // rather than a 500.
                // A code picked here applies to every company added; left blank,
                // each company brings its own usual code (set on its page), if any.
                $code = $data['code'] ?? $company->kraf_code;

                $registration = EventRegistration::firstOrCreate(
                    ['event_id' => $event->id, 'company_id' => $company->id],
                    [
                        'user_id' => $company->user_id,
                        'code' => $code,
                        'code_updated_at' => $code ? now() : null,
                        // Same for the booth size; 3x3 when neither says.
                        'plot_size' => $data['plot_size'] ?? $company->plot_size ?? '3x3',
                    ]
                );

                $added += $registration->wasRecentlyCreated ? 1 : 0;

                // A size picked for the batch becomes the company's own too.
                if ($registration->wasRecentlyCreated && isset($data['plot_size'])) {
                    $company->syncBoothSize($data['plot_size']);
                }
            }
        });

        return back()->with('success', $added === 1
            ? 'One company added to the draw list.'
            : "{$added} companies added to the draw list.");
    }

    /** Set this row's craft code or booth size. */
    public function update(Request $request, Event $event, EventRegistration $registration)
    {
        EventVisibility::authorize($event);
        $this->authorizeScope($request, $registration);

        $data = $request->validate([
            'code' => ['nullable', 'string', 'max:50'],
            'plot_size' => ['required', Rule::in(PlotSizes::all())],
        ]);

        if ($registration->code !== ($data['code'] ?? null)) {
            $data['code_updated_at'] = now();
        }

        DB::transaction(function () use ($registration, $data) {
            $registration->update($data);
            // The company's booth size follows, and with it its other draw lists.
            $registration->company->syncBoothSize($data['plot_size']);
        });

        return back()->with('code_success', 'Saved.');
    }

    /**
     * Take one company back out of the draw.
     *
     * A row holding a lot is refused rather than deleted. The draw result is the
     * thing this whole application exists to produce, and it may not disappear as
     * a side effect of tidying a list — the lot is withdrawn first, deliberately,
     * on the draw's own screen.
     */
    public function destroy(Request $request, Event $event, EventRegistration $registration)
    {
        EventVisibility::authorize($event);
        $this->authorizeScope($request, $registration);

        if ($registration->plot_id) {
            // Worded the same for a set-aside lot, which a zone admin must not learn of.
            return back()->with('error', $request->user('admin')->hasGlobalAccess()
                ? 'That company holds a lot. Withdraw the lot before removing it from the list.'
                : 'Only HQ can remove that company from the list now.');
        }

        $this->rememberForUndo([$registration]);

        $registration->delete();

        return back()->with('success', 'Removed from the draw list.');
    }

    /**
     * One action over many ticked rows: remove them, or set a code or a size.
     *
     * The ids are re-read through this admin's scope rather than trusted, so a
     * hand-edited checkbox cannot reach another zone's row.
     */
    public function bulk(Request $request, Event $event)
    {
        EventVisibility::authorize($event);

        $data = $request->validate([
            'action' => ['required', Rule::in(['remove', 'set_code', 'set_size'])],
            'registration_id' => ['required', 'array', 'min:1'],
            'registration_id.*' => ['integer'],
            'code' => ['required_if:action,set_code', 'nullable', 'string', 'max:50'],
            'plot_size' => ['required_if:action,set_size', 'nullable', Rule::in(PlotSizes::all())],
        ]);

        $admin = $request->user('admin');

        $rows = $event->registrations()
            ->whereIn('id', $data['registration_id'])
            ->unless($admin->hasGlobalAccess(), fn ($q) => $q->whereHas(
                'company', fn ($c) => $c->where('state', $admin->state)
            ))
            ->get();

        if ($rows->isEmpty()) {
            return back()->with('error', 'Nothing matched.');
        }

        if ($data['action'] === 'remove') {
            $holding = $rows->whereNotNull('plot_id');
            $removable = $rows->whereNull('plot_id');

            $this->rememberForUndo($removable);

            // Through the model one at a time, not a mass delete: `Auditable`
            // listens to Eloquent events, and a query-builder delete fires none
            // of them — the rows would go with nothing written about them.
            DB::transaction(fn () => $removable->each->delete());

            return back()->with('success', trim(sprintf(
                '%d removed from the draw list.%s',
                $removable->count(),
                $holding->isEmpty() ? '' : ($admin->hasGlobalAccess()
                    ? " {$holding->count()} kept: they hold lots."
                    : " {$holding->count()} kept: only HQ can remove them now.")
            )));
        }

        $changes = $data['action'] === 'set_code'
            ? ['code' => $data['code'], 'code_updated_at' => now()]
            : ['plot_size' => $data['plot_size']];

        DB::transaction(function () use ($rows, $changes, $data) {
            $rows->each->update($changes);

            // A size set here becomes each company's own, and moves its other draw lists.
            if ($data['action'] === 'set_size') {
                $rows->load('company')->pluck('company')->unique('id')
                    ->each->syncBoothSize($data['plot_size']);
            }
        });

        return back()->with('success', "{$rows->count()} updated.");
    }

    /**
     * Put back what the last removal took.
     *
     * The rows are rebuilt from what was stored, not resurrected — their ids are
     * gone and nothing pointed at them, because a row holding a lot is never
     * removed in the first place. One step deep: the session holds the last
     * removal only, which is the one an admin is still looking at.
     */
    public function undo(Request $request, Event $event)
    {
        EventVisibility::authorize($event);

        $rows = collect(session('draw_list_undo', []))
            ->where('event_id', $event->id);

        if ($rows->isEmpty()) {
            return back()->with('error', 'Nothing to undo.');
        }

        $companies = $this->scopedCompanies($request, $rows->pluck('company_id')->all())->keyBy('id');

        DB::transaction(function () use ($rows, $companies) {
            foreach ($rows as $row) {
                if (! $companies->has($row['company_id'])) {
                    continue;
                }

                EventRegistration::firstOrCreate(
                    ['event_id' => $row['event_id'], 'company_id' => $row['company_id']],
                    collect($row)->except(['event_id', 'company_id'])->all()
                );
            }
        });

        session()->forget('draw_list_undo');

        return back()->with('success', 'Put back.');
    }

    /**
     * @param  \Illuminate\Support\Collection<int, EventRegistration>|array<int, EventRegistration>  $registrations
     */
    private function rememberForUndo($registrations): void
    {
        session(['draw_list_undo' => collect($registrations)
            ->take(self::UNDO_LIMIT)
            ->map(fn (EventRegistration $r) => [
                'event_id' => $r->event_id,
                'company_id' => $r->company_id,
                'user_id' => $r->user_id,
                'code' => $r->code,
                'code_updated_at' => $r->code_updated_at,
                'plot_size' => $r->plot_size,
                'confirmed_participation' => $r->confirmed_participation,
                'confirmed_at' => $r->confirmed_at,
            ])
            ->values()
            ->all()]);
    }

    /**
     * @param  array<int, int>  $ids
     * @return \Illuminate\Support\Collection<int, Company>
     */
    private function scopedCompanies(Request $request, array $ids)
    {
        $admin = $request->user('admin');

        return Company::whereIn('id', $ids)
            ->unless($admin->hasGlobalAccess(), fn ($q) => $q->where('state', $admin->state))
            ->get();
    }

    private function authorizeScope(Request $request, EventRegistration $registration): void
    {
        $admin = $request->user('admin');

        abort_if(
            ! $admin->hasGlobalAccess() && $registration->company->state !== $admin->state,
            403,
            'That company is administered by another zone.'
        );
    }
}
