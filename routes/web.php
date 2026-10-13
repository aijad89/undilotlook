<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Auth\Admin\LoginController as AdminLoginController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

/*
 * FOUR ROUTE NAMES ARE LOAD-BEARING BEFORE ANY SCREEN IS BUILT, because
 * bootstrap/app.php resolves them on every redirect:
 *
 *   login            redirectGuestsTo, for a non-/admin URL
 *   admin.login      redirectGuestsTo, for an /admin/* URL
 *   dashboard        redirectUsersTo,  for a non-/admin URL
 *   admin.dashboard  redirectUsersTo,  for an /admin/* URL
 *
 * Remove or rename any of them and the failure is not a 404 on that page — it is
 * a RouteNotFoundException thrown out of the middleware, on whichever unrelated
 * page happened to trigger a redirect.
 */

/* ── Public ──────────────────────────────────────────────────────────────── */

Route::get('/', [HomeController::class, 'index'])->name('home');

/* ── Participant portal ──────────────────────────────────────────────────── */

Route::middleware(['auth', 'user.active'])->group(function () {
    Route::get('/dashboard', [HomeController::class, 'dashboard'])->name('dashboard');
});

/* ── Admin ───────────────────────────────────────────────────────────────── */

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest:admin')->group(function () {
        Route::get('login', [AdminLoginController::class, 'showLoginForm'])->name('login');
        Route::post('login', [AdminLoginController::class, 'login']);
    });

    Route::middleware(['auth:admin', 'track.admin-last-seen'])->group(function () {
        Route::post('logout', [AdminLoginController::class, 'logout'])->name('logout');
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    });
});

require __DIR__.'/auth.php';
