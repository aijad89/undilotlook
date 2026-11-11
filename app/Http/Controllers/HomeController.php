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
            'onList' => $company && $event->registrations()->where('company_id', $company->id)->exists(),
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

        $entries = $user->registrations()
            ->with(['event', 'plot.plotSize'])
            ->get()
            ->reject(fn ($r) => $r->event->polling_status === 'cancelled')
            // Dated windows first, soonest first; undated ones after.
            ->sortBy(fn ($r) => $r->event->polling_start_date?->timestamp ?? PHP_INT_MAX)
            ->values();

        // Requests not yet in a draw: what was sent, and where it stands. An
        // event the company is already in (approved, or added by the office) is
        // a draw entry above, and is not repeated; an approved request left over
        // means the office took the company off the list since. A cancelled or
        // unpublished event drops out while its registration is still running;
        // after it closed, the request stays to say the event was called off.
        $requests = \App\Models\EventJoinRequest::with('event')
            ->where('company_id', $user->company?->id)
            ->whereIn('status', ['draft', 'pending', 'rejected', 'approved'])
            ->whereNotIn('event_id', $user->registrations()->pluck('event_registrations.event_id'))
            ->latest('updated_at')
            ->get()
            ->reject(fn ($jr) => $jr->event->isCalledOff() && ! $jr->event->registration_close_at?->isPast())
            ->values();

        return view('dashboard', [
            'user' => $user,
            'entries' => $entries,
            'requests' => $requests,
            'drawn' => \App\Models\Poll::where('user_id', $user->id)->pluck('event_id')->flip(),
        ]);
    }
}
