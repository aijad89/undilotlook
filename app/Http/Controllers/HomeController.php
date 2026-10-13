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
     * The participant's own dashboard.
     *
     * PHASE 1: the shell, so the login flow has somewhere to land. It grows the
     * draw list and the lot result in phase 3.
     */
    public function dashboard(Request $request)
    {
        return view('dashboard', [
            'user' => $request->user(),
        ]);
    }
}
