<?php

use App\Models\Admin;
use App\Models\Company;
use App\Models\EventRegistration;
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
        ->and(EventRegistration::count())->toBe($zones);
});
