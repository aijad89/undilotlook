<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request): View
    {
        return view('auth.reset-password', [
            'request' => $request,
            'token' => $request->route('token'),
        ]);
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                // A RESET EVICTS THE USER'S OTHER DEVICES; a routine password change
                // does not. Both halves were decided together, and this is where the
                // distinction is enforced: the eviction lives INSIDE this callback, so
                // it is part of the reset and nothing else can reach it by accident.
                $this->logOutOtherDevices($user);

                event(new PasswordReset($user));
            }
        );

        // If the password was successfully reset, we will redirect the user back to
        // the application's home authenticated view. If there is an error we can
        // redirect them back to where they came from with their error message.
        return $status == Password::PASSWORD_RESET
                    ? redirect()->route('login')->with('status', __($status))
                    : back()->withInput($request->only('email'))
                        ->withErrors(['email' => __($status)]);
    }

    /**
     * Delete this user's other portal sessions, so a reset actually evicts whoever
     * else was logged in.
     *
     * WHY ROWS AND NOT `Auth::logoutOtherDevices()`. That helper needs
     * `AuthenticateSession` on the route stack to have any effect, and that middleware
     * CANNOT be limited to this path. Measured 2026-09-08: it compares the session's
     * stored password hash against the user's current one
     * (`Session/Middleware/AuthenticateSession.php:66`) and logs out on mismatch, with no
     * knowledge of which code path changed the hash — and it enforces on the OTHER
     * device's next request, to some other route entirely. Installing it delivers
     * "EVERY password change evicts other devices", which is a different decision from
     * the one above. Driven by hand in the system this came from: after an ordinary
     * password change, a device holding the old hash got 302 → /login.
     *
     * NO "EXCEPT THE CURRENT SESSION" LOGIC, and that is not an omission. The reset flow
     * never logs anybody in — store() above redirects to route('login') — so the person
     * resetting is a guest at this moment and has no session to preserve. Every session
     * for this user goes.
     *
     * THE PAYLOAD CHECK IS THE POINT OF THIS METHOD, not the delete. `sessions.user_id`
     * holds ids from `admins` AND `users` with nothing to tell them apart:
     * `DatabaseSessionHandler::userId()` reads the DEFAULT guard, and
     * `Auth/Middleware/Authenticate.php:83` switches the default guard to `admin` for
     * any `auth:admin` request. Live ids are admins 1-19 and users 1-277, so a bare
     * `where('user_id', $user->id)->delete()` would log out the ADMIN SHARING THAT ID.
     * In the system this came from that was 19 admins against 277 users — about 7% of
     * resets — and the admin had no way to connect being logged out to somebody else's
     * password reset.
     *
     * So each candidate row is opened and kept only if its payload carries a `login_web_*`
     * key equal to this user's id. Bounded work on a rare path: one indexed query and a
     * handful of rows per user.
     */
    private function logOutOtherDevices(User $user): void
    {
        // DRIVER-COUPLED, DELIBERATELY, and it must say so out loud when it stops
        // applying. This evicts by deleting rows from the `sessions` TABLE, so on any
        // other driver it matches nothing: the reset reports success and the other
        // devices stay signed in, with no error and no failing test. The warning below
        // is the only thing that would tell anyone.
        if (config('session.driver') !== 'database') {
            Log::warning('Password reset did not evict other devices: session driver is not `database`', [
                'driver'  => config('session.driver'),
                'user_id' => $user->id,
                'note'    => 'Evicting other devices on reset requires the database session driver.',
            ]);

            return;
        }

        $table = config('session.table', 'sessions');
        $rows = DB::table($table)->where('user_id', $user->id)->get(['id', 'payload']);

        $mine = $rows->filter(fn ($row) => $this->payloadBelongsToWebUser($row->payload, $user->id))
            ->pluck('id');

        if ($mine->isEmpty()) {
            return;
        }

        DB::table($table)->whereIn('id', $mine)->delete();
    }

    /**
     * Does this session payload belong to THIS user on the `web` guard?
     *
     * The payload is a base64-encoded serialized array whose login key is
     * `login_web_<sha1 of the guard's provider class>`. The suffix is not worth
     * reconstructing — matching the `login_web_` prefix is what separates a portal
     * session from an `login_admin_` one, and the value is the id to compare.
     *
     * Anything unreadable returns false: a payload this cannot parse is a row this must
     * not delete.
     */
    private function payloadBelongsToWebUser(?string $payload, int $userId): bool
    {
        if ($payload === null || $payload === '') {
            return false;
        }

        $decoded = base64_decode($payload, true);

        if ($decoded === false) {
            return false;
        }

        $data = @unserialize($decoded, ['allowed_classes' => false]);

        if (! is_array($data)) {
            return false;
        }

        foreach ($data as $key => $value) {
            if (is_string($key) && str_starts_with($key, 'login_web_')) {
                return (int) $value === $userId;
            }
        }

        return false;
    }
}
