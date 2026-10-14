<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Support\AdminZones;
use App\Support\CalendarPalette;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

/**
 * Events, and nothing inside them. The draw list is EntRegistrationController's
 * screen and the plan is the lot draw's; this one owns the event row.
 *
 * WRITES ARE GATED, READS ARE SCOPED. `can:manage-events` guards create through
 * destroy in routes/web.php. index() is open to every role and narrows itself
 * instead — a state admin sees every event that is not a draft, which is the
 * same question EventVisibility::authorize() answers for a single event. Two
 * expressions of one rule, because a list has to filter where a page has to
 * refuse.
 */
class EventController extends Controller
{
    public function index(Request $request)
    {
        $admin = $request->user('admin');

        $events = Event::query()
            ->withCount('registrations')
            ->when($request->input('search'), fn ($q, $search) => $q->where(
                fn ($w) => $w->where('title', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
            ))
            ->when($request->input('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->input('state'), fn ($q, $state) => $q->where('state', $state))
            // A state admin has to see an empty event to put their first
            // company into it; whose rows they see inside is the draw list's job.
            ->unless($admin->hasGlobalAccess(), fn ($q) => $q->where('status', '!=', 'draft'))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.events.index', [
            'events' => $events,
            'canManage' => $admin->canManageEvents(),
        ]);
    }

    /** The same events as the list, on a month grid. */
    public function calendar()
    {
        return view('admin.events.calendar', ['palette' => CalendarPalette::PALETTE]);
    }

    /**
     * The calendar's feed: each event, its registration window and its voting
     * window, as three bars. Same filters and the same scope as index(), so the
     * list and the calendar show one result set.
     */
    public function calendarData(Request $request)
    {
        $admin = $request->user('admin');

        $events = Event::query()
            ->when($request->input('search'), fn ($q, $search) => $q->where(
                fn ($w) => $w->where('title', 'like', "%{$search}%")->orWhere('location', 'like', "%{$search}%")
            ))
            ->when($request->input('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->input('state'), fn ($q, $state) => $q->where('state', $state))
            ->unless($admin->hasGlobalAccess(), fn ($q) => $q->where('status', '!=', 'draft'))
            ->get();

        $palette = CalendarPalette::PALETTE;

        return response()->json($events->flatMap(function (Event $event) use ($palette) {
            $colour = CalendarPalette::forEvent($event);
            $own = $event->colour !== null;
            $link = route('admin.events.registrations.index', $event);

            $bars = [[
                'id' => 'event-'.$event->id,
                'title' => $event->title,
                'start' => $event->start_date->toDateString(),
                // FullCalendar's end is exclusive.
                'end' => $event->end_date->copy()->addDay()->toDateString(),
                'color' => $colour,
                'url' => $link,
                'extendedProps' => ['status' => $event->status, 'location' => $event->location],
            ]];

            // A normal bar rather than a background tint, so concurrent windows
            // stack on their own labelled rows instead of compounding into one
            // darker patch that names nobody.
            if ($event->registration_open_at && $event->registration_close_at) {
                $bars[] = [
                    'id' => 'reg-'.$event->id,
                    'title' => 'Registration: '.$event->title,
                    'start' => $event->registration_open_at->toDateString(),
                    'end' => $event->registration_close_at->copy()->addDay()->toDateString(),
                    'classNames' => ['reg-window'],
                    'backgroundColor' => $own ? CalendarPalette::tint($colour) : $palette['registration_fill'],
                    'borderColor' => $own ? $colour : $palette['registration_border'],
                    'textColor' => $own ? $colour : $palette['registration_text'],
                    'url' => $link,
                    'extendedProps' => [
                        'type' => 'registration', 'eventTitle' => $event->title,
                        'location' => $event->location, 'status' => $event->status,
                    ],
                ];
            }

            if ($event->polling_start_date && $event->polling_deadline) {
                $bars[] = [
                    'id' => 'poll-'.$event->id,
                    'title' => 'Voting: '.$event->title,
                    'start' => $event->polling_start_date->toDateString(),
                    'end' => $event->polling_deadline->copy()->addDay()->toDateString(),
                    'color' => $palette['polling'],
                    'url' => route('admin.polling.show', $event),
                    'extendedProps' => ['type' => 'polling'],
                ];
            }

            return $bars;
        })->values());
    }

    public function create()
    {
        return view('admin.events.create', ['event' => new Event]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $data['banner'] = $this->storeBanner($request);

        $event = Event::create($data);

        return redirect()->route('admin.events.index')
            ->with('success', "Event \"{$event->title}\" created.");
    }

    public function edit(Event $event)
    {
        return view('admin.events.edit', ['event' => $event]);
    }

    public function update(Request $request, Event $event)
    {
        $data = $this->validated($request);

        $oldBanner = $event->banner;

        if ($new = $this->storeBanner($request)) {
            $data['banner'] = $new;
        }

        $event->update($data);

        // The database write is the reversible one, so it goes first and the
        // irreversible act goes last. A failed update must not leave the row
        // naming a file that is already gone.
        if ($new && $oldBanner) {
            Storage::disk('public')->delete($oldBanner);
        }

        return redirect()->route('admin.events.index')
            ->with('success', "Event \"{$event->title}\" saved.");
    }

    /**
     * TWO CASCADES OUT OF THIS ROW COLLIDE, and clearing `plot_id` first is what
     * keeps them apart.
     *
     * `events → event_registrations` and `events → plots` both CASCADE, while
     * `event_registrations.plot_id` has NO delete rule at all — so the database
     * RESTRICTS. If it reaches the plots before the registrations that point at
     * them, the DELETE fails with a foreign key error that cannot explain itself
     * to an admin. The order is not specified anywhere, so it is not something to
     * rely on either way.
     *
     * Clearing the link is also what the draw's own rule demands: a draw result
     * may not evaporate as a side effect. Here it is going anyway — but it goes
     * in a statement that says so.
     */
    public function destroy(Event $event)
    {
        $banner = $event->banner;

        DB::transaction(function () use ($event) {
            $event->registrations()->whereNotNull('plot_id')->update(['plot_id' => null]);

            $event->delete();
        });

        if ($banner) {
            Storage::disk('public')->delete($banner);
        }

        return redirect()->route('admin.events.index')
            ->with('success', "Event \"{$event->title}\" deleted.");
    }

    /**
     * One rule set for create and update.
     *
     * `state` is a ZONE, from AdminZones — the office that runs the event, which
     * is also what a zone rename rewrites. Not geography: `location` is where the
     * event actually is, and it is free text.
     *
     * `polling_status`, `use_preset`, `draw_animation` and `total_plots` are
     * deliberately absent. They belong to the draw, which has its own screen and
     * computes `total_plots` from the plan rather than taking it from a form.
     *
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'location' => ['required', 'string', 'max:255'],
            'state' => ['nullable', AdminZones::rule()],

            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'registration_open_at' => ['required', 'date'],
            'registration_close_at' => ['required', 'date', 'after:registration_open_at'],

            'status' => ['required', Rule::in(array_keys(Event::STATUSES))],
            'max_participants' => ['required', 'integer', 'min:1'],
            'colour' => ['nullable', Rule::in(array_keys(CalendarPalette::EVENT_COLOURS))],
            'banner' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        // Unchecked boxes do not arrive at all, so they have to be read from the
        // request rather than from $data — otherwise switching one off never saves.
        $data['auto_open'] = $request->boolean('auto_open');
        $data['auto_close'] = $request->boolean('auto_close');

        unset($data['banner']);

        return $data;
    }

    /**
     * Resize to the banner's display size and store it, or null when none was
     * uploaded.
     *
     * Intervention's save() does not create missing directories, and
     * `storage/app/public/events` is not in the repository — so a first upload on
     * a fresh clone fails without the mkdir.
     */
    private function storeBanner(Request $request): ?string
    {
        if (! $request->hasFile('banner')) {
            return null;
        }

        $filename = 'events/banner_'.time().'_'.uniqid().'.jpg';
        $path = Storage::disk('public')->path($filename);

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        (new ImageManager(new Driver))
            ->read($request->file('banner'))
            ->cover(797, 235)
            ->save($path, 80, 'jpg');

        return $filename;
    }
}
