<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * The public landing page.
     *
     * PHASE 1: there is no public content yet, so this forwards rather than
     * rendering an empty shell — a signed-in visitor to their own dashboard, and
     * everyone else to the login form. When the public event listing lands this
     * becomes that page, and the forward applies only to the signed-in case.
     *
     * It redirects to the ADMIN dashboard for an admin session. Without that
     * branch an admin who clicks the logo lands on the participant login form,
     * because `/` is a guest route and an admin-guard session is a guest on the
     * `web` guard.
     */
    public function index(Request $request)
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }

        if (Auth::guard('web')->check()) {
            return redirect()->route('dashboard');
        }

        return redirect()->route('login');
    }

    /**
     * The participant's own dashboard: every draw their company is in, and what
     * is left to do in each.
     *
     * A draw still in draft shows only once it has a voting window — a date is
     * "not yet", where an empty page reads as "you have nothing". Cancelled draws
     * do not show. How many lots exist stays withheld, as on the vote page.
     */
    public function dashboard(Request $request)
    {
        $user = $request->user();

        $entries = $user->registrations()
            ->with(['event', 'plot.plotSize'])
            ->get()
            ->filter(fn ($r) => $r->event->polling_status !== 'cancelled'
                && ($r->event->polling_status !== 'draft' || $r->event->polling_start_date))
            ->sortBy(fn ($r) => $r->event->polling_start_date)
            ->values();

        return view('dashboard', [
            'user' => $user,
            'entries' => $entries,
            'drawn' => \App\Models\Poll::where('user_id', $user->id)->pluck('event_id')->flip(),
        ]);
    }
}
