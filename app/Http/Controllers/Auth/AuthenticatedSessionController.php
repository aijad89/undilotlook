<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        // intended('/'), not '/'. The auth middleware stores the URL a guest was
        // bounced from; without intended() it is stored and never read, so a
        // participant who followed a link to an event or to the draw while
        // logged out lands on the home page with no way back but to find the
        // link again. '/' stays the fallback for a direct visit to the login
        // page.
        return redirect()->intended('/');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Always '/', never intended(): logging out is not a bounce, and the
        // session has just been invalidated anyway.
        return redirect('/');
    }
}
