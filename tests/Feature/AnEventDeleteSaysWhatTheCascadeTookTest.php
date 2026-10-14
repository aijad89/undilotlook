<?php

use App\Models\AuditTrail;
use App\Models\Company;
use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Support\Facades\DB;

/**
 * Rows removed by ON DELETE CASCADE fire no Eloquent event, so they leave no
 * audit row of their own. Deleting an event destroys rows in six tables and used
 * to write one line saying nothing about any of them.
 *
 * The count has to happen BEFORE the DELETE — afterwards there is nothing left
 * to count — which is the whole reason it lives in a `deleting` hook.
 */
it('records every reached table on the deleted row, zeros included', function () {
    $event = Event::factory()->create();

    EventRegistration::factory()->count(2)->create(['event_id' => $event->id]);

    $sizeId = DB::table('plot_sizes')->insertGetId([
        'event_id' => $event->id,
        'size' => '3x3',
        'quantity' => 4,
        'location' => 'Dewan A',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('polls')->insert([
        'user_id' => Company::factory()->create()->user_id,
        'event_id' => $event->id,
        'code' => 'TBPS',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $event->delete();

    $summary = AuditTrail::where('auditable_type', Event::class)
        ->where('auditable_id', $event->id)
        ->where('action', 'deleted')
        ->value('new_values');

    expect($summary['cascade_deleted'])->toBe([
        'event_registrations' => 2,
        'plot_sizes' => 1,
        'polls' => 1,
        'bidang_allocations' => 0,
        'plots' => 0,
        'preset_allocations' => 0,
    ])
        // Present and empty rather than absent: nothing survives an event delete
        // having merely lost a reference, and a shape that changes with the data
        // cannot be compared against another delete.
        ->and($summary['links_cleared'])->toBe([]);

    expect(DB::table('plot_sizes')->where('id', $sizeId)->exists())->toBeFalse();
});

it('counts a plot reachable through plot_sizes as well as through the event', function () {
    $event = Event::factory()->create();

    $sizeId = DB::table('plot_sizes')->insertGetId([
        'event_id' => $event->id,
        'size' => '3x6',
        'quantity' => 1,
        'location' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Carries both an event_id and a plot_size_id, and both cascade. Counting
    // either column alone would still report 1 here — the OR is what keeps the
    // number honest when a row carries only one of them.
    DB::table('plots')->insert([
        'event_id' => $event->id,
        'plot_size_id' => $sizeId,
        'company_id' => null,
        'allocated_to' => null,
        'bidang_id' => null,
        'sub_bidang_id' => null,
        'plot_code' => 'A1',
        'is_allocated' => false,
        'allocation_type' => 'algorithm',
        'is_locked' => false,
        'allocated_at' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect($event->collectDeletionCounts()['cascade_deleted']['plots'])->toBe(1);
});
