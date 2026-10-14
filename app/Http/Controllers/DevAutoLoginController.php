<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Click-to-login for development: become any account without a password.
 *
 * On any real host this is a complete authentication bypass, so it exists on a
 * developer's machine and nowhere else — and it is fenced twice:
 *
 *   1. routes/web.php registers these routes only in `local` and `testing`, so
 *      elsewhere the URL does not exist at all.
 *   2. Every action re-checks, because route registration is easy to break in
 *      a refactor and a guard beside the dangerous code is not.
 *
 * `testing` sits beside `local` so the suite can reach the guard at all. It
 * aborts 404, not 403: a production host should not confirm the route exists.
 *
 * `daftar` also opened this on its dev server behind a flag and an IP list.
 * That door is not carried: undilot's /auto is for this machine only.
 */
class DevAutoLoginController extends Controller
{
    /**
     * The page is a wall of buttons carrying the session's CSRF token. Sign in as
     * someone else, press Back, and a cached copy posts an old token — a 419.
     */
    private const NO_STORE = ['Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0'];

    private function ensureLocal(): void
    {
        abort_unless(app()->environment(['local', 'testing']), 404);
    }

    public function index()
    {
        $this->ensureLocal();

        return response()->view('dev.auto-login', [
            'admins' => Admin::orderBy('role')->orderBy('state')->orderBy('email')->get(),
            'users' => User::with('company')->orderBy('id')->get(),
        ], 200, self::NO_STORE);
    }

    public function loginAdmin(Request $request, Admin $admin)
    {
        $this->ensureLocal();
        $this->log($request, 'admin', $admin->email);

        // Drop the other guard first, or one session holds a user and an admin
        // at once and the two portals disagree about who you are.
        auth()->guard('web')->logout();
        auth()->guard('admin')->login($admin);
        $request->session()->regenerate();

        return redirect()->route('admin.dashboard');
    }

    public function loginUser(Request $request, User $user)
    {
        $this->ensureLocal();
        $this->log($request, 'web', $user->email);

        auth()->guard('admin')->logout();
        auth()->guard('web')->login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        $this->ensureLocal();

        auth()->guard('web')->logout();
        auth()->guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('dev.auto');
    }

    /** Who became whom. Nothing here changes a row, so it is a log line, not an audit row. */
    private function log(Request $request, string $guard, string $email): void
    {
        Log::warning('dev auto-login', ['guard' => $guard, 'to' => $email, 'ip' => $request->ip()]);
    }
}
