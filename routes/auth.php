<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use Illuminate\Support\Facades\Route;

/*
 * NO REGISTRATION ROUTE, AND NO EMAIL VERIFICATION.
 *
 * Accounts are created by an admin from the Company screen — one company, one
 * account — so there is no self-service sign-up to verify an address for. The
 * admin types the address and hands over the password.
 *
 * That makes `is_active` the switch that matters rather than `email_verified_at`,
 * and EnforceUserIsActive turns switching it off into an immediate logout rather
 * than a session that quietly keeps working.
 */

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');

    /*
     * `throttle:10,1`, and LOOSER THAN ITS NEIGHBOURS ON PURPOSE.
     *
     * WHY A LIMIT AT ALL, given this is not the weak point. The token is 64 hex
     * characters from Str::random(64) and is stored hashed, so guessing it is not
     * a practical attack. The limiter is here because every other end of this flow
     * has one and this end would otherwise have none: POST /login rate-limits
     * inside LoginRequest::ensureIsNotRateLimited(), and POST /forgot-password is
     * limited by the password broker's own `throttle` in config/auth.php rather
     * than by route middleware. Three ends, three mechanisms — leaving one empty
     * is how it gets forgotten.
     *
     * WHY 10 AND NOT 6. `throttle` keys on IP for guests, and the office sits
     * behind one NAT, so the budget is shared by everyone in the building rather
     * than being per person. Six a minute is a plausible number of colleagues
     * resetting passwords after a batch of accounts is created; ten leaves room
     * without giving up the point, since what this guards is volume.
     */
    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('password.store');
});

Route::middleware(['auth', 'user.active'])->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

/*
 * ONE WARNING FOR WHOEVER ADDS A CHANGE-PASSWORD ROUTE HERE LATER, because the
 * mistake it describes shipped once and took six months to surface.
 *
 * A ROUTE NAME THAT RESOLVES BUT DOES NOT WORK IS WORSE THAN NO NAME AT ALL.
 *
 * In the system this repo came from, `auth.php` declared
 *
 *     Route::put('password', [PasswordController::class, 'update'])->name('password.update');
 *
 * behind `auth`, and the GUEST reset-password form pointed at
 * `route('password.update')`. The name resolved, so there was no
 * RouteNotFoundException at render, the page looked right, and it reviewed
 * clean. It failed only on submit — as a 405, in production, to somebody trying
 * to recover an account they were locked out of. A POST from a guest matches
 * nothing, Laravel's checkForAlternateVerbs() finds the PUT, and the request
 * dies as MethodNotAllowedHttpException. Nothing upstream of the submit can see
 * it coming.
 *
 * With no such name declared, the same mistake throws at render on the first
 * page load instead.
 */
