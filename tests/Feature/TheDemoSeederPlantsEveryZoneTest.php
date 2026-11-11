<?php

use App\Models\Admin;
use App\Models\Company;
use App\Models\Event;
use App\Models\EventJoinRequest;
use App\Models\EventRegistration;
use App\Models\Plot;
use App\Models\Poll;
use App\Support\AdminZones;
use Database\Seeders\DemoSeeder;
use Database\Seeders\KrafCodeSeeder;

/**
 * The demo seeder plants one state admin and one company per zone, and running
 * it twice adds nothing — so it keeps working as the schema moves.
 */
it('plants one admin and one company per zone, and is safe to run twice', function () {
    $this->seed([KrafCodeSeeder::class, DemoSeeder::class]);
    $this->seed(DemoSeeder::class);

    $zones = count(AdminZones::DEFAULTS);

    expect(Admin::where('role', 'admin')->distinct()->count('state'))->toBe($zones)
        ->and(Admin::where('role', 'admin')->count())->toBe($zones)
        ->and(Company::distinct()->count('state'))->toBe($zones)
        ->and(Company::count())->toBe($zones)
        ->and(Company::whereNull('kraf_code')->count())->toBe(0)
        ->and(Company::whereDoesntHave('krafSelections')->count())->toBe(0)
        ->and(EventRegistration::whereHas('event', fn ($q) => $q->where('title', 'Demo Kraf Fair 2027'))->count())->toBe($zones);
});

it('plants open, coming and past events, the past ones with lots, slips and the answers sent', function () {
    $this->seed([KrafCodeSeeder::class, DemoSeeder::class]);
    $counts = fn () => [Event::count(), EventRegistration::count(), Plot::count(), Poll::count(), EventJoinRequest::count()];
    $first = $counts();

    $past = Event::where('end_date', '<', now())->get();
    expect($past)->toHaveCount(5)
        ->and($past->every(fn ($e) => $e->status === 'completed' && $e->polling_status === 'completed'))->toBeTrue()
        // Open for registration now: three, and one more coming later.
        ->and(Event::get()->filter->acceptsJoinRequests()->count())->toBeGreaterThanOrEqual(3)
        ->and(Event::where('registration_open_at', '>', now())->where('status', 'open')->exists())->toBeTrue();

    // Every lot belongs to a row on its own event's list, and is confirmed.
    $lots = EventRegistration::whereNotNull('plot_id')->with('plot')->get();
    expect($lots)->not->toBeEmpty()
        ->and($lots->every(fn ($r) => $r->plot->event_id === $r->event_id && $r->plot->company_id === $r->company_id))->toBeTrue()
        ->and($lots->every(fn ($r) => Poll::where('user_id', $r->user_id)->where('event_id', $r->event_id)->exists()))->toBeTrue();

    // The answers each company sent, on approved requests of past events.
    $requests = EventJoinRequest::whereIn('event_id', $past->pluck('id'))->get();
    expect($requests)->not->toBeEmpty()
        ->and($requests->every(fn ($jr) => $jr->status === 'approved' && ! empty($jr->answers['contact_name']) && count($jr->answers['product_images']) === 5))->toBeTrue();

    // A second run adds nothing.
    $this->seed(DemoSeeder::class);
    expect($counts())->toBe($first);
});
