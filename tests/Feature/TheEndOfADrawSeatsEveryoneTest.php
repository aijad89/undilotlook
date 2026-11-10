<?php

use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Plot;
use App\Models\PlotSize;
use App\Models\Poll;

/**
 * After voting closes. A lot set aside for a company that never confirmed is
 * still owed to it: the bulk run hands it over, and "completed" waits for it.
 * The dashboard tells the office when a closed draw still has people waiting.
 */
function closedDrawWithAnUntakenSetAside(): array
{
    $event = Event::factory()->create([
        'status' => 'open', 'polling_status' => 'polling',
        'polling_start_date' => now()->subDays(2), 'polling_deadline' => now()->addDay(),
        'title' => 'Closed Fair',
    ]);
    $size = PlotSize::factory()->create(['event_id' => $event->id]);
    $plot = Plot::factory()->on($size)->create(['plot_code' => 'HALL A-SET1']);
    $entry = EventRegistration::factory()->create(['event_id' => $event->id, 'code' => 'RKP']);

    test()->actingAs(superAdmin(), 'admin')->post(route('admin.polling.preset-assign', $event), [
        'plot_id' => $plot->id, 'registration_id' => $entry->id,
    ])->assertSessionHas('success');

    $event->update(['polling_status' => 'closed', 'polling_deadline' => now()->subHour()]);
    auth()->forgetGuards();

    return [$event, $entry->fresh()];
}

it('hands an untaken set-aside lot over on Assign remaining lots, opening its lot page and slip', function () {
    [$event, $entry] = closedDrawWithAnUntakenSetAside();
    $user = $entry->company->user;

    // Before: the lot is reserved but the company cannot reach it.
    $this->actingAs($user, 'web')->get(route('zvote.index', $event))->assertForbidden();

    $this->actingAs(superAdmin(), 'admin')->post(route('admin.polling.process', $event))
        ->assertSessionHas('success');

    expect($entry->fresh()->confirmed_participation)->toBeTrue()
        ->and(Poll::where('user_id', $user->id)->where('event_id', $event->id)->exists())->toBeTrue()
        ->and($event->fresh()->polling_status)->toBe('completed');

    auth()->forgetGuards();
    $this->actingAs($user, 'web')->get(route('zvote.index', $event))->assertOk()->assertSee('HALL A-SET1');
    $this->actingAs($user, 'web')->get(route('zvote.slip', $event))->assertOk();
});

it('refuses Mark completed while a set-aside lot is still untaken', function () {
    [$event] = closedDrawWithAnUntakenSetAside();

    $this->actingAs(superAdmin(), 'admin')
        ->post(route('admin.polling.change-status', $event), ['polling_status' => 'completed'])
        ->assertSessionHasErrors('error');

    expect($event->fresh()->polling_status)->toBe('closed');
});

it('tells super and HQ when a closed draw still has companies without a lot', function () {
    [$event] = closedDrawWithAnUntakenSetAside();
    EventRegistration::factory()->create(['event_id' => $event->id, 'code' => 'RKP']);

    $this->actingAs(superAdmin(), 'admin')->get(route('admin.dashboard'))
        ->assertSee('Closed Fair: voting closed, 2 companies still without a lot');

    auth()->forgetGuards();
    $this->actingAs(stateAdmin('Johor'), 'admin')->get(route('admin.dashboard'))
        ->assertDontSee('still without a lot');
});

it('points the office at Set aside when the bulk run cannot place someone', function () {
    $event = Event::factory()->create(['status' => 'open', 'polling_status' => 'closed']);
    EventRegistration::factory()->create(['event_id' => $event->id, 'code' => 'RKP']);

    $this->actingAs(superAdmin(), 'admin')->post(route('admin.polling.process', $event))
        ->assertSessionHas('warning', fn ($w) => str_contains($w, 'use Set aside on a free plot'));
});
