<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/**
 * Ejects a `web` user whose own account, or whose company, has been deactivated.
 *
 * This is the ONLY place that enforcement happens for the web guard. Deactivation is
 * decided elsewhere — an admin toggles `users.is_active` or `companies.is_active` — and
 * without this middleware on a route group, an already-logged-in user keeps their session
 * and keeps working. Removing it from a group re-admits deactivated accounts silently.
 *
 * It was called `TrackUserLastSeen`, alias `track.last-seen`, until 2026-09-09. The name
 * described the second half of the method and hid the first: a route group reading
 * `['auth', 'track.last-seen']` looks like it carries bookkeeping that could be trimmed.
 *
 * The last-seen write stays here as a documented side effect rather than moving to its own
 * middleware: it is two cache reads and one throttled `saveQuietly()` on a request that has
 * already loaded the user, and splitting it would put a second class on every user route.
 * `TrackAdminLastSeen` is the admin-guard twin and does only the tracking half — the
 * `admins` table has no `is_active` column, so there is nothing there to enforce.
 */
class EnforceUserIsActive
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user) {
            if (! $user->is_active || ($user->company && ! $user->company->is_active)) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                // withErrors, not with('error'): the login page shows only
                // $errors and `status`, so a flashed `error` is never seen.
                return redirect()->route('login')->withErrors(['email' => $user->is_active
                    ? 'Your company has been deactivated. Please contact the administrator.'
                    : 'Your account has been deactivated. Please contact the administrator.']);
            }

            Cache::put("online_status_web_{$user->id}", now(), now()->addMinutes(5));

            $dbKey = "last_seen_db_user_{$user->id}";
            if (!Cache::has($dbKey)) {
                $user->last_seen_at = now();
                $user->saveQuietly();
                Cache::put($dbKey, true, now()->addSeconds(60));
            }
        }

        return $next($request);
    }
}
