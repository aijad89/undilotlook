<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * The public landing page: every published event, upcoming first.
     *
     * Read-only for everyone. A participant asks to join from the event's own
     * page; the office approves. How many lots an event holds stays off it, as
     * it does everywhere a participant looks.
     */
    public function index(Request $request)
    {
        $events = Event::where('status', '!=', 'draft')
            ->when($request->input('search'), fn ($q, $search) => $q->where(
                fn ($w) => $w->where('title', 'like', "%{$search}%")->orWhere('location', 'like', "%{$search}%")
            ))
            ->get();

        [$past, $upcoming] = $events->partition(fn (Event $e) => $e->end_date->isPast());

        // Past events come six at a time; "More" asks for six more by URL, so it
        // needs no script and a reload keeps the place.
        $pastStep = 6;
        $pastShown = min(max((int) $request->input('past', $pastStep), $pastStep), 600);

        return view('home', [
            'upcoming' => $upcoming->sortBy('start_date')->values(),
            'past' => $past->sortByDesc('start_date')->values(),
            'pastShown' => $pastShown,
            'pastStep' => $pastStep,
        ]);
    }

    /**
     * One published event, for anyone. A draft is not public, so it is a 404 —
     * the same as an id that does not exist.
     */
    public function show(Event $event)
    {
        abort_if($event->status === 'draft', 404);

        // What a signed-in participant's company has to do with this event: on
        // the draw list, a join request in flight, or neither.
        $company = auth('web')->user()?->company;

        return view('events.show', [
            'event' => $event,
            'company' => $company,
            'onList' => $company && $event->registrations()->announced()->where('company_id', $company->id)->exists(),
            // Put on the list by the office, waiting for HQ: no form to fill in.
            'beingAdded' => $company && $event->registrations()->whereNull('first_verified_at')->where('company_id', $company->id)->exists(),
            'joinRequest' => $company ? $event->joinRequests()->where('company_id', $company->id)->first() : null,
        ]);
    }

    /**
     * The participant's own dashboard: every draw their company is in, and what
     * is left to do in each.
     *
     * Every draw list the company is on shows, even before a voting window is
     * set: an empty page reads as "you are in nothing", which is false the moment
     * the office adds them. Cancelled draws do not show. How many lots exist stays
     * withheld, as on the vote page.
     */
    public function dashboard(Request $request)
    {
        $user = $request->user();
        $company = $user->company;
        $onList = $user->registrations()->announced()->pluck('event_registrations.event_id');

        $entries = $user->registrations()->announced()
            ->with(['event', 'plot.plotSize'])
            ->get()
            ->reject(fn ($r) => $r->event->polling_status === 'cancelled')
            // An event that has ended is on Past events, not here.
            ->reject(fn ($r) => $r->event->end_date->isPast())
            // Latest event first; on the same day, the newer event first.
            ->sortBy([fn ($a, $b) => [$b->event->start_date->timestamp, $b->event_id] <=> [$a->event->start_date->timestamp, $a->event_id]])
            ->values();

        // Requests not yet in a draw: what was sent, and where it stands. An
        // event the company is already in (approved, or added by the office) is
        // a draw entry above, and is not repeated; an approved request left over
        // means the office took the company off the list since. A called-off
        // event (draw cancelled, or back to draft) drops out while its
        // registration is still running; after it closed, the request stays to
        // say the event was called off.
        $requests = \App\Models\EventJoinRequest::with('event')
            ->where('company_id', $company?->id)
            ->whereIn('status', ['draft', 'pending', 'rejected', 'approved'])
            ->whereNotIn('event_id', $onList)
            ->latest('updated_at')
            ->get()
            ->reject(fn ($jr) => $jr->event->isCalledOff() && ! $jr->event->registration_close_at?->isPast())
            // An ended event is done with, as for the draw entries above.
            ->reject(fn ($jr) => $jr->event->end_date->isPast())
            ->values();

        $drawn = \App\Models\Poll::where('user_id', $user->id)->pluck('event_id')->flip();

        // Open for registration and not yet touched: no request of any kind, and
        // not on the list. Soonest to close first.
        $openEvents = $company
            ? Event::where('status', 'open')->where('polling_status', 'draft')
                ->where('registration_open_at', '<=', now())->where('registration_close_at', '>=', now())
                // Any row, verified or not: one waiting for HQ is not open to join either.
                ->whereNotIn('id', $company->eventRegistrations()->pluck('event_id'))
                ->whereNotIn('id', \App\Models\EventJoinRequest::where('company_id', $company->id)->pluck('event_id'))
                ->orderBy('registration_close_at')
                ->get()
            : collect();

        return view('dashboard', [
            'user' => $user,
            'company' => $company,
            'entries' => $entries,
            'requests' => $requests,
            'drawn' => $drawn,
            'openEvents' => $openEvents,
            'todo' => $this->todo($entries, $requests, $drawn, $company),
        ]);
    }

    /**
     * The events the company took part in that have ended, latest first: the
     * lot it had, and what it answered when it asked to join each; then the
     * answers its next request starts from.
     */
    public function pastEvents(Request $request)
    {
        $user = $request->user();

        $entries = $user->registrations()->announced()
            ->with(['event', 'plot'])
            ->get()
            ->filter(fn ($r) => $r->event->end_date->isPast() && $r->event->polling_status !== 'cancelled')
            ->sortByDesc(fn ($r) => $r->event->start_date->timestamp)
            ->values();

        // What the company answered for each, as it stood when it asked to join:
        // the request keeps its own copy. Only an approved request put the
        // company on the list; a rejected or draft one did not, and none
        // approved means the office added it.
        $requests = \App\Models\EventJoinRequest::where('company_id', $user->company?->id)
            ->where('status', 'approved')
            ->whereIn('event_id', $entries->pluck('event_id'))
            ->get()
            ->keyBy('event_id');

        return view('past-events', [
            'entries' => $entries,
            'requests' => $requests,
            'company' => $user->company,
            // A slip is printed only for a lot the participant confirmed.
            'drawn' => \App\Models\Poll::where('user_id', $user->id)->pluck('event_id')->flip(),
        ]);
    }

    /**
     * One past event's summary, for a company that took part in it. Any other
     * event, from here, does not exist: 404, as on the lot page.
     */
    public function pastEvent(Request $request, Event $event)
    {
        $company = $request->user()->company;

        abort_unless(
            $company && $event->end_date->isPast() && $event->polling_status !== 'cancelled'
                && $event->registrations()->announced()->where('company_id', $company->id)->exists(),
            404
        );

        return view('past-event', ['event' => $event]);
    }

    /**
     * What is waiting on the participant, most pressing first: a lot to
     * confirm, a request to finish or send again, a certificate running out.
     * Each item is [tone, sentence, link or null, link words].
     */
    private function todo($entries, $requests, $drawn, $company): array
    {
        $todo = [];

        foreach ($entries as $entry) {
            $event = $entry->event;
            if ($event->polling_status === 'polling' && ! $event->polling_deadline?->isPast() && ! isset($drawn[$event->id])) {
                $todo[] = ['bad', "Confirm your lot for {$event->title} before {$event->polling_deadline->format('d M Y, h:i A')}.", route('zvote.index', $event), 'Confirm Lot'];
            }
        }

        foreach ($requests as $jr) {
            if (! $jr->event->acceptsJoinRequests()) {
                continue;
            }
            $closes = $jr->event->registration_close_at->format('d M Y');
            $todo[] = match ($jr->status) {
                'draft' => ['warn', "Finish your request to join {$jr->event->title}. Registration closes {$closes}.", route('events.show', $jr->event), 'Finish and send'],
                'rejected' => ['warn', "The office did not approve your request to join {$jr->event->title}. Change it and send it again by {$closes}.", route('events.show', $jr->event), 'Change and send again'],
                'approved' => ['warn', "Your company was taken off the draw list for {$jr->event->title}."
                    .($jr->removed_reason ? " Reason: {$jr->removed_reason}" : '')
                    ." You may ask to join again by {$closes}.", route('events.show', $jr->event), 'Ask to join again'],
                default => null,
            };
        }

        // A certificate on record: expired, or within two months of it.
        foreach (['classification_cert' => 'classification certificate', 'quality_cert' => 'quality compliance certificate'] as $key => $name) {
            $end = $company?->credentials[$key]['end'] ?? null;
            if (! $end) {
                continue;
            }
            $end = \Illuminate\Support\Carbon::parse($end)->endOfDay();
            if ($end->isPast()) {
                $todo[] = ['bad', "Your {$name} expired on {$end->format('d M Y')}. Renew it before you next ask to join an event.", null, null];
            } elseif ($end->lte(now()->addMonths(2))) {
                $todo[] = ['warn', "Your {$name} expires on {$end->format('d M Y')}. Renew it in time for your next event.", null, null];
            }
        }

        // Most pressing first; the order within a tone is kept.
        $rank = ['bad' => 0, 'warn' => 1, 'info' => 2];

        return collect(array_filter($todo))->sortBy(fn ($item) => $rank[$item[0]])->values()->all();
    }
}
