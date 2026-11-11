<?php

namespace App\Providers;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        /*
         * The signed-in caller's unread-notification count, resolved at most once
         * per request.
         *
         * `scoped()` rather than `singleton()`, or a variable captured by the
         * composer closure: a scoped binding is flushed between requests, so this
         * cannot go stale if this application is ever put behind Octane, where a
         * provider boots once and serves many requests. Under FPM the two behave
         * identically; the difference shows up on the day someone changes the
         * runtime, which is exactly when nobody is reading this file.
         *
         * Memoisation here is not an optimisation, it is required for the view
         * composer below to be worth having: `dashboard` extends
         * `layouts.frontend`, so both names fire on a single dashboard render.
         * Without this the composer would trade N queries for 2.
         */
        $this->app->scoped('notifications.unread-count', function () {
            foreach (['web', 'admin'] as $guard) {
                if (Auth::guard($guard)->check()) {
                    return Auth::guard($guard)->user()->unreadNotifications()->count();
                }
            }

            return 0;
        });

        /*
         * Settings rows already read this request, key => value.
         *
         * `Setting::get()` is one SELECT per call, and the callers that matter
         * call it once per rendered row — a managed dropdown list read from
         * inside a table loop is one read per row for a value that cannot change
         * mid-page.
         *
         * `scoped()` for the same reason as above, and it matters more here: a
         * `static` array on the class would survive between requests under
         * Octane, so an admin editing a list in Settings would be served the old
         * one until the worker restarted. A scoped binding cannot outlive the
         * request that filled it.
         *
         * `ArrayObject` rather than an array because the binding is resolved once
         * and mutated by every later reader; an array would be copied by value.
         *
         * Staleness WITHIN a single request is closed at the other end, in
         * `Setting::set()`, rather than by trusting that no writer renders a view.
         */
        $this->app->scoped('settings.memo', fn () => new \ArrayObject);

        /*
         * `artisan serve` must keep handing the child process a temp directory.
         *
         * WHAT BREAKS WITHOUT THIS. ServeCommand starts `php -S` as a child and
         * builds its environment from `$_ENV` — but only passes everything
         * through when NO environment was set. The moment `APP_ENV` is present it
         * switches to a whitelist, `ServeCommand::$passthroughVariables`, and
         * every other variable is set to `false`. That list is fourteen names
         * long and `TMP` and `TEMP` are not on it.
         *
         * On Windows PHP has no other source for its temp directory, so the
         * served application loses the ability to create one, and every multipart
         * upload dies at request startup:
         *
         *     Warning: PHP Request Startup: File upload error - unable to create
         *     a temporary file in Unknown on line 0
         *
         * The warning is printed INTO THE RESPONSE BODY, ahead of the JSON, so an
         * XHR handler's `r.json()` throws and the failure arrives in `.catch()`
         * rather than as something the screen could show. In the system this
         * replaces, thirteen of fourteen browser-suite failures were this one line.
         *
         * WHY HERE AND NOT `--no-reload`. That flag also takes the pass-everything
         * branch, so it does fix the symptom — by disabling file watching, a
         * feature with nothing to do with uploads, and only for the runs that
         * remember it. `$passthroughVariables` is `public static` precisely so an
         * application can extend it.
         *
         * Guarded on the class existing because the framework owns it, and on
         * running in the console because there is nothing to fix in a web request.
         */
        if ($this->app->runningInConsole() && class_exists(\Illuminate\Foundation\Console\ServeCommand::class)) {
            foreach (['TMP', 'TEMP', 'TMPDIR'] as $variable) {
                if (! in_array($variable, \Illuminate\Foundation\Console\ServeCommand::$passthroughVariables, true)) {
                    \Illuminate\Foundation\Console\ServeCommand::$passthroughVariables[] = $variable;
                }
            }
        }
    }

    public function boot(): void
    {
        /*
         * TWO OF ELOQUENT'S THREE STRICT-MODE FLAGS, AND THE THIRD IS LEFT OFF ON PURPOSE.
         *
         * Not `Model::shouldBeStrict()`, which turns on all three. The missing one
         * is `preventLazyLoading`. It is omitted because a regression test that
         * MEASURES lazy reads with `DB::listen` has to be able to perform one, and
         * because "this read is deliberately lazy" is a decision that should be
         * overturned by argument rather than by a flag. Two of three is the
         * configuration, not an oversight.
         *
         * WHAT THE OTHER TWO BUY, measured in the system this replaces by enabling
         * each one alone against its full suite:
         *
         *   preventAccessingMissingAttributes — 25 failures, nearly all harness.
         *       `actingAs($user)` hands a view the factory's own instance, which
         *       never held the columns the factory did not set, while a real login
         *       retrieves every column. Closed by naming the nullable columns in
         *       the factory — and by three genuine production-reachable 500s it
         *       surfaced on the way.
         *
         *   preventSilentlyDiscardingAttributes — 220 failures, five causes, then
         *       two more the first five were masking. Six of seven were writes to
         *       COLUMNS THAT NO LONGER EXISTED, named by fixtures months after the
         *       column was dropped. Without the flag such a write is a silent
         *       no-op; the value simply never arrives.
         *
         * GATED TO NON-PRODUCTION. In production a missing attribute should render
         * blank rather than 500 a participant's page — these are a development
         * instrument, not a runtime policy. The same `isProduction()` line that
         * DatabaseSeeder uses, deliberately.
         */
        if (! $this->app->isProduction()) {
            Model::preventAccessingMissingAttributes();
            Model::preventSilentlyDiscardingAttributes();
        }

        Paginator::useBootstrapFive();

        /*
         * The password floor.
         *
         * Laravel's own default is min:8 and nothing else, which accepts
         * '12345678'. min(8) + letters + numbers, and deliberately no more:
         *
         *   rejects  '12345678' (no letter), 'password' (no number)
         *   accepts  'kraf1234', 'Kraftangan2026'
         *
         * No mixedCase() and no symbols(): the readers are craft entrepreneurs,
         * and a floor they cannot clear turns into a phone call to the office.
         *
         * No uncompromised(): it puts an outbound HTTPS call to the Pwned
         * Passwords API in the middle of every password change. It fails open, so
         * the cost is latency rather than lockout, but a network dependency in a
         * write path is a deployment decision, not a default.
         *
         * IT APPLIES WHERE A PASSWORD IS SET, NEVER WHERE ONE IS CHECKED. Login
         * has no strength rule at all, so raising this floor later cannot lock
         * anybody out of an account they already have.
         *
         * Accounts here are created by an admin, so the admin-side create and
         * reset forms consult this too — not just the self-service reset. A floor
         * that only guards the door the user walks through is not a floor.
         */
        Password::defaults(fn () => Password::min(8)->letters()->numbers());

        /*
         * NO ResetPassword::createUrlUsing() HERE, AND THAT IS DELIBERATE.
         *
         * The Sanctum SPA scaffolding ships one, and it builds
         * `config('app.frontend_url')."/password-reset/$token"`. For a Blade
         * monolith both halves are wrong: there is no `/password-reset/{token}`
         * route — the real one is `/reset-password/{token}` — and
         * `app.frontend_url` is not defined in this application's config, so it
         * resolves to the FRAMEWORK default of `http://localhost:3000` and never
         * reaches APP_URL. The emailed link is then unreachable twice over.
         *
         * Laravel's default builds `route('password.reset', [token, email])`,
         * which is the route that exists and honours APP_URL. If one is ever added
         * here it has to be kept in step with routes/auth.php by hand, which is
         * the part that drifts.
         */

        /*
         * A named gate rather than a policy per model: the things it guards —
         * footer menus and social links — are not one model and share no parent,
         * and the question asked of each is the same one.
         *
         * None of it is state-scoped, which is the actual reason it needs a gate
         * at all: one footer is read by every usahawan in the country, so a state
         * admin editing it is editing thirteen other states' footer.
         */
        Gate::define('manage-site-content', function (Admin $admin) {
            return $admin->canManageSiteContent();
        });

        /*
         * Who may create, edit and delete events — and with them the draw that
         * lives inside each one.
         *
         * READ IS NOT GATED. A state admin has to reach an event to reach its
         * draw list, so the listing and the draw list stay open to every role and
         * are SCOPED instead, by EventVisibility and by the queries themselves.
         * Gating the read as well would leave a state admin unable to see the
         * event their own companies are drawing in.
         */
        Gate::define('manage-events', function (Admin $admin) {
            return $admin->canManageEvents();
        });

        /*
         * The dropdown lists, the craft hierarchy and the coding sheet.
         *
         * National, every one of them: one craft code list is read by all
         * fourteen zones, so a state admin editing it is editing thirteen other
         * zones' vocabulary. Zones themselves are stricter still and are checked
         * again inside SettingController — they decide who sees what, so they are
         * super-only.
         */
        Gate::define('manage-settings', function (Admin $admin) {
            return $admin->hasGlobalAccess();
        });

        /*
         * Sending an announcement. Reading is not gated — Announcement::
         * visibleToAdmin() narrows what each admin sees — but sending is not a
         * state admin's job, whoever would receive it.
         */
        Gate::define('send-announcements', function (Admin $admin) {
            return $admin->hasGlobalAccess();
        });

        /*
         * The admin-accounts screens. Reaching them is super and hq; WHO each may
         * act on and WHICH role each may hand out are decided per request inside
         * AdminController, by authorizeTarget() and Admin::assignableRolesFor() —
         * hq works on state admins only.
         */
        Gate::define('manage-admins', function (Admin $admin) {
            return $admin->hasGlobalAccess();
        });

        /*
         * The craft code and booth size a company draws under, on the draw list
         * and on the company's own page. The plan is built from those two, so
         * HQ sets them; a zone checks the payment, approves, and reads them.
         * The draw-list routes also serve zone admins (approve, add, remove), so
         * there EntRegistrationController asks this gate per request and drops
         * a code or size a zone admin sends.
         */
        Gate::define('set-draw-code', function (Admin $admin) {
            return $admin->hasGlobalAccess();
        });

        /* The audit trail. hq included, Admin rows and all — see AuditTrailController. */
        Gate::define('read-audit-trail', function (Admin $admin) {
            return $admin->hasGlobalAccess();
        });

        /*
         * ┌─ THE VIEWS THAT READ $unreadNotificationCount. ADD A FOURTH HERE. ─┐
         *
         * Any view that starts reading `$unreadNotificationCount` MUST be named in
         * this list. A view that reads it without being listed gets an undefined
         * variable, and the readers do not guard with `isset()` — so that is a
         * 500, not a silently blank badge.
         *
         * NOT `View::composer('*')`, which runs the COUNT for every Blade view
         * COMPOSED — every partial, not every page. In the system this replaces
         * that measured 53 queries on a twelve-row admin screen, for a value no
         * admin view displays at all. Scoping the list is what takes an admin page
         * to zero; memoising alone only takes it to one.
         */
        View::composer(['dashboard', 'partials.portal-bar', 'notifications.index'], function ($view) {
            $view->with('unreadNotificationCount', app('notifications.unread-count'));
        });
    }

    // No $policies array, on purpose. This class extends the plain
    // ServiceProvider, which never reads one — an array here would be inert while
    // looking like the place authorization is wired up.
}
