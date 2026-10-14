<?php

use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Admin\AuditTrailController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EntRegistrationController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\PlotPainterController;
use App\Http\Controllers\Admin\PollingController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Auth\Admin\LoginController as AdminLoginController;
use App\Http\Controllers\DevAutoLoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LotDrawController;
use App\Http\Controllers\NotificationController;
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

    Route::get('/events/{event}/lot', [LotDrawController::class, 'show'])->name('zvote.index');
    Route::post('/events/{event}/lot', [LotDrawController::class, 'submit'])->name('zvote.submit');
    Route::get('/events/{event}/lot/slip', [LotDrawController::class, 'slip'])->name('zvote.slip');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'read'])->name('notifications.read');
});

/*
 * An announcement's banner or file: reached from either portal, so either guard
 * may ask. The controller decides whether this caller may have it.
 */
Route::get('/announcements/{announcement}/{kind}', [NotificationController::class, 'file'])
    ->middleware('auth:web,admin')
    ->whereIn('kind', ['banner', 'attachment'])
    ->name('announcements.file');

/* ── Dev: click-to-login ─────────────────────────────────────────────────── */

/*
 * An authentication bypass by design, so it is registered on a developer's
 * machine and in the suite, and nowhere else. The controller checks again.
 */
if (app()->environment(['local', 'testing'])) {
    Route::get('/auto', [DevAutoLoginController::class, 'index'])->name('dev.auto');
    Route::post('/auto/admin/{admin}', [DevAutoLoginController::class, 'loginAdmin'])->name('dev.auto.admin');
    Route::post('/auto/user/{user}', [DevAutoLoginController::class, 'loginUser'])->name('dev.auto.user');
    Route::post('/auto/logout', [DevAutoLoginController::class, 'logout'])->name('dev.auto.logout');
    Route::post('/auto/queue', [DevAutoLoginController::class, 'workQueue'])->name('dev.auto.queue');
}

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
         * The lot draw. Reads are open to every role and scope themselves;
         * everything that writes the plan or moves the draw is super/hq only.
         * The static paths are declared before `{event}` so "create" is never
         * read as an event id.
         */
        Route::prefix('polling')->name('polling.')->group(function () {
            Route::get('/', [PollingController::class, 'index'])->name('index');

            Route::middleware('can:manage-events')->group(function () {
                Route::get('create', [PollingController::class, 'create'])->name('create');
                Route::post('create', [PollingController::class, 'storeSettings'])->name('store-settings');
                Route::get('create/plan', [PollingController::class, 'createPlan'])->name('create-plan');
                Route::post('/', [PollingController::class, 'store'])->name('store');
                Route::get('{event}/edit', [PollingController::class, 'edit'])->name('edit');
                Route::put('{event}', [PollingController::class, 'update'])->name('update');
                Route::delete('{event}', [PollingController::class, 'destroyPolling'])->name('destroy');
                Route::post('{event}/generate-plots', [PollingController::class, 'generatePlots'])->name('generate-plots');
                Route::post('{event}/status', [PollingController::class, 'changeStatus'])->name('change-status');
                Route::post('{event}/process', [PollingController::class, 'processResults'])->name('process');
                Route::post('{event}/draw-animation', [PollingController::class, 'updateDrawAnimation'])->name('draw-animation');
                Route::post('{event}/preset', [PollingController::class, 'presetAssign'])->name('preset-assign');
                Route::post('{event}/plots/{plot}/unassign', [PollingController::class, 'unassignPlot'])->name('unassign-plot');
                Route::get('{event}/paint', [PlotPainterController::class, 'edit'])->name('paint');
                Route::put('{event}/paint', [PlotPainterController::class, 'update'])->name('paint.update');
            });

            Route::get('{event}', [PollingController::class, 'show'])->name('show');
            Route::get('{event}/sizes/{plotSize}', [PollingController::class, 'viewPlotsBySize'])->name('plots.by-size');
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
        /* Announcements: every admin reads what reached them; super/hq send. */
        Route::get('announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
        Route::middleware('can:send-announcements')->group(function () {
            Route::get('announcements/create', [AnnouncementController::class, 'create'])->name('announcements.create');
            Route::post('announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
        });
        Route::get('announcements/{announcement}', [AnnouncementController::class, 'show'])->name('announcements.show');

        /* The audit trail: read-only, super and hq. */
        Route::middleware('can:read-audit-trail')->group(function () {
            Route::get('audit-trails', [AuditTrailController::class, 'index'])->name('audit-trails.index');
            Route::get('audit-trails/{auditTrail}', [AuditTrailController::class, 'show'])->name('audit-trails.show');
        });

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
