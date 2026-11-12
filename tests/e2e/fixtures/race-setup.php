<?php

// One open draw with ONE free lot and two companies wanting it. Prints the two
// registration ids as JSON, for race-draw.php.

use App\Models\Company;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\KrafBidang;
use App\Models\Plot;

$event = Event::create([
    'title' => 'Race Fair '.uniqid(),
    'location' => 'Dewan Lumba',
    'status' => 'open',
    'polling_status' => 'polling',
    'polling_start_date' => now()->subHour(),
    'polling_deadline' => now()->addHour(),
    'start_date' => now()->addMonth(),
    'end_date' => now()->addMonth()->addDay(),
    'registration_open_at' => now()->subWeek(),
    'registration_close_at' => now()->addWeek(),
    'max_participants' => 10,
]);

$size = $event->plotSizes()->create(['location' => 'Lumba', 'size' => '3x3', 'quantity' => 1]);

Plot::create([
    'event_id' => $event->id,
    'plot_size_id' => $size->id,
    'bidang_id' => KrafBidang::codes()->where('code', 'RKP')->value('id'),
    'plot_code' => 'LUMBA-RKP1',
    'is_allocated' => false,
    'allocation_type' => 'algorithm',
    'is_locked' => false,
]);

$ids = [];

foreach (['a', 'b'] as $who) {
    $company = Company::factory()->create(['name' => "Racer {$who}"]);
    $ids[] = EventRegistration::create([
        'event_id' => $event->id,
        'company_id' => $company->id,
        'user_id' => $company->user_id,
        'code' => 'RKP',
        'plot_size' => '3x3',
        'verified_at' => now(),
        'first_verified_at' => now(),
    ])->id;
}

echo json_encode(['event' => $event->id, 'registrations' => $ids]).PHP_EOL;
