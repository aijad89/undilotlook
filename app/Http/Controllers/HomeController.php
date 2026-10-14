<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * The public landing page: every published event, upcoming first.
     *
     * Read-only for everyone. There is no self-registration — the office puts a
     * company into a draw — so the page invites a participant to sign in to
     * confirm a lot, never to register. How many lots an event holds stays off
     * it, as it does everywhere a participant looks.
     */
    public function index(Request $request)
    {
        $events = Event::where('status', '!=', 'draft')
            ->when($request->input('search'), fn ($q, $search) => $q->where(
                fn ($w) => $w->where('title', 'like', "%{$search}%")->orWhere('location', 'like', "%{$search}%")
            ))
            ->get();

        [$past, $upcoming] = $events->partition(fn (Event $e) => $e->end_date->isPast());

        return view('home', [
            'upcoming' => $upcoming->sortBy('start_date')->values(),
            'past' => $past->sortByDesc('start_date')->take(12)->values(),
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

        return view('dashboard', [
            'user' => $user,
            'entries' => $entries,
            'drawn' => \App\Models\Poll::where('user_id', $user->id)->pluck('event_id')->flip(),
        ]);
    }
}
