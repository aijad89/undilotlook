<?php

use App\Http\Middleware\EnforceUserIsActive;
use App\Http\Middleware\TrackAdminLastSeen;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        /*
         * NO API GROUP, deliberately. This is a Blade monolith with two session
         * guards and no token issuer — nothing here could satisfy `auth:sanctum`,
         * because no model uses `HasApiTokens`. If an API is ever wanted, adding
         * the group is the small half; deciding how an entrepreneur authenticates
         * without a session is the real work.
         */

        /*
         * Two of the three logout routes, so an expired session logs out instead
         * of hitting a 419 on the way.
         *
         * The three are POST /logout, POST /admin/logout and POST /auto/logout,
         * and the third is deliberately NOT here: /auto is the click-to-login dev
         * group, registered only inside `if (app()->environment(['local','testing']))`,
         * so it never exists on a deployment where an expired session could reach
         * it. Neither entry below is a prefix of any other URI, so nothing
         * over-matches.
         */
        $middleware->validateCsrfTokens(except: [
            'logout',
            'admin/logout',
        ]);

        $middleware->alias([
            // Presence tracking, NOT authorization. State scoping is done per
            // method through App\Support\EventVisibility — a middleware alias on
            // every admin route reads like a guard and would enforce nothing.
            'track.admin-last-seen' => TrackAdminLastSeen::class,
            'user.active' => EnforceUserIsActive::class,
        ]);

        // An unauthenticated visit to an /admin/* route must land on the ADMIN
        // login page. Laravel's default redirectTo() ignores which guard the route
        // belongs to and always falls back to the `login` route.
        $middleware->redirectGuestsTo(function (\Illuminate\Http\Request $request) {
            return $request->is('admin/*') ? route('admin.login') : route('login');
        });

        // THE SAME PROBLEM IN THE OTHER DIRECTION, and it is easy to miss.
        //
        // An admin already signed in who opens /admin/login is redirected away by
        // `guest:admin`. Laravel's default target is /dashboard — with no
        // RouteServiceProvider and no HOME constant, the framework default applies.
        // /dashboard is behind the `web` guard, where an admin-guard session is a
        // guest, so redirectGuestsTo above then sends them to route('login'): an
        // admin, signed in, looking at the user login form. Two clicks on Sign In
        // is enough to produce it.
        $middleware->redirectUsersTo(function (\Illuminate\Http\Request $request) {
            return $request->is('admin/*') ? route('admin.dashboard') : route('dashboard');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
