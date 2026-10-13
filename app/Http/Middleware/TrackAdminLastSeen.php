<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Presence tracking for the admin guard: online-status cache and a throttled
 * last_seen_at. Nothing else.
 *
 * THIS DOES NOT ENFORCE STATE SCOPING, AND NEVER DID.
 *
 * It was called AdminStateAccess and aliased `admin.state`, and it sat on the
 * whole admin route group — 191 routes — reading like the thing that keeps a
 * state admin inside their own state. It does not. It has no abort, no
 * redirect, no query constraint and no container binding; it returns
 * $next($request) unconditionally on every path. It was once documented as
 * scoping data access by admin state, which was never true of a single line in
 * this file.
 *
 * State scoping in this application is done by hand, in the controller, as the
 * first statement of a method: scopeToAdminState(), authorizeCompany(),
 * authorizeUser(), authorizeImageState(), or an inline hasGlobalAccess() check.
 * 103 of those 191 routes do it that way; 49 more are gated to super/hq so
 * state does not apply; the rest are covered by can: middleware or are already
 * recorded in.
 *
 * If you are here because you want scoping enforced centrally, that is a real
 * design change and it is not this file's job. Add it deliberately, and move
 * every controller that does it by hand through App\Support\EventVisibility.
 *
 * @see \App\Http\Middleware\EnforceUserIsActive  the web-guard twin, which DOES
 *      enforce something — it logs out a deactivated user. The two are named
 *      for what they do, which is why only one of them reads like a guard.
 */
class TrackAdminLastSeen
{
    public function handle(Request $request, Closure $next)
    {
        $admin = $request->user('admin');

        if ($admin) {
            // Always refresh the online-status cache (used by isOnline())
            Cache::put("online_status_admin_{$admin->id}", now(), now()->addMinutes(5));

            // Write to DB at most once per minute to avoid a write on every request
            $dbKey = "last_seen_db_admin_{$admin->id}";
            if (!Cache::has($dbKey)) {
                $admin->last_seen_at = now();
                $admin->saveQuietly();
                Cache::put($dbKey, true, now()->addSeconds(60));
            }
        }

        return $next($request);
    }
}
