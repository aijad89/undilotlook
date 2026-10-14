<?php

use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EntRegistrationController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\SettingController;
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

        /*
         * Events. The LISTING is open to every admin role and scopes itself — a
         * state admin has to reach an event to reach its draw list. Only the
         * writes are gated.
         */
        Route::get('events', [EventController::class, 'index'])->name('events.index');

        Route::middleware('can:manage-events')->group(function () {
            Route::get('events/create', [EventController::class, 'create'])->name('events.create');
            Route::post('events', [EventController::class, 'store'])->name('events.store');
            Route::get('events/{event}/edit', [EventController::class, 'edit'])->name('events.edit');
            Route::put('events/{event}', [EventController::class, 'update'])->name('events.update');
            Route::delete('events/{event}', [EventController::class, 'destroy'])->name('events.destroy');
        });

        /*
         * The draw list for one event. `scopeBindings()` ties {registration} to
         * {event}: an id from another event resolves to a 404 rather than being
         * loaded and then checked — or, worse, not checked.
         */
        Route::prefix('events/{event}/registrations')
            ->name('events.registrations.')
            ->scopeBindings()
            ->group(function () {
                Route::get('/', [EntRegistrationController::class, 'index'])->name('index');
                Route::post('/', [EntRegistrationController::class, 'store'])->name('store');
                Route::post('bulk', [EntRegistrationController::class, 'bulk'])->name('bulk');
                Route::post('undo', [EntRegistrationController::class, 'undo'])->name('undo');
                Route::put('{registration}', [EntRegistrationController::class, 'update'])->name('update');
                Route::delete('{registration}', [EntRegistrationController::class, 'destroy'])->name('destroy');
            });

        /*
         * Companies. Not gated: a state admin manages the companies in their own
         * zone, which is the larger part of the job. Every action scopes instead.
         */
        Route::get('companies', [CompanyController::class, 'index'])->name('companies.index');
        Route::get('companies/create', [CompanyController::class, 'create'])->name('companies.create');
        Route::post('companies', [CompanyController::class, 'store'])->name('companies.store');
        Route::get('companies/{company}/edit', [CompanyController::class, 'edit'])->name('companies.edit');
        Route::put('companies/{company}', [CompanyController::class, 'update'])->name('companies.update');
        Route::delete('companies/{company}', [CompanyController::class, 'destroy'])->name('companies.destroy');

        Route::post('companies/{company}/kraf', [CompanyController::class, 'storeKraf'])->name('companies.kraf.store');
        Route::delete('companies/{company}/kraf/{selection}', [CompanyController::class, 'destroyKraf'])
            ->name('companies.kraf.destroy');

        /* Settings — national lists, so super and HQ only. */
        Route::middleware('can:manage-settings')->prefix('settings')->name('settings.')->group(function () {
            Route::get('lists', [SettingController::class, 'lists'])->name('lists');
            Route::put('lists/{list}', [SettingController::class, 'updateList'])->name('lists.update');

            Route::post('zones', [SettingController::class, 'storeZone'])->name('zones.store');
            Route::put('zones', [SettingController::class, 'renameZone'])->name('zones.rename');
            Route::delete('zones', [SettingController::class, 'destroyZone'])->name('zones.destroy');

            Route::get('kraf-codes', [SettingController::class, 'krafCodes'])->name('kraf-codes');
            Route::post('kraf-codes', [SettingController::class, 'storeKrafCode'])->name('kraf-codes.store');
            Route::put('kraf-codes/{code}', [SettingController::class, 'updateKrafCode'])->name('kraf-codes.update');
            Route::post('kraf-codes/{code}/toggle', [SettingController::class, 'toggleKrafCode'])->name('kraf-codes.toggle');
            Route::delete('kraf-codes/{code}', [SettingController::class, 'destroyKrafCode'])->name('kraf-codes.destroy');

            Route::get('kraf-hierarchy', [SettingController::class, 'krafHierarchy'])->name('kraf-hierarchy');
            Route::post('kraf-hierarchy', [SettingController::class, 'storeCategory'])->name('kraf-hierarchy.store');
            Route::delete('kraf-hierarchy/{category}', [SettingController::class, 'destroyCategory'])
                ->name('kraf-hierarchy.destroy');
            Route::post('kraf-hierarchy/{category}/sub', [SettingController::class, 'storeSubBidang'])
                ->name('kraf-hierarchy.sub.store');
            Route::delete('kraf-hierarchy/sub/{subBidang}', [SettingController::class, 'destroySubBidang'])
                ->name('kraf-hierarchy.sub.destroy');
            Route::post('kraf-hierarchy/sub/{subBidang}/pengelasan', [SettingController::class, 'storePengelasan'])
                ->name('kraf-hierarchy.pengelasan.store');
            Route::delete('kraf-hierarchy/pengelasan/{pengelasan}', [SettingController::class, 'destroyPengelasan'])
                ->name('kraf-hierarchy.pengelasan.destroy');
            Route::post('kraf-hierarchy/{category}/produk', [SettingController::class, 'storeProduk'])
                ->name('kraf-hierarchy.produk.store');
            Route::delete('kraf-hierarchy/produk/{produk}', [SettingController::class, 'destroyProduk'])
                ->name('kraf-hierarchy.produk.destroy');
        });
    });
});

require __DIR__.'/auth.php';
