<?php

use App\Models\Event;
use App\Support\CalendarPalette;

/**
 * The events calendar: the list's result set as bars — the event, its
 * registration window and its voting window.
 */
it('feeds the event, its registration window and its voting window as three bars', function () {
    $event = Event::factory()->create([
        'status' => 'open',
        'colour' => '#1E88E5',
        'start_date' => '2026-12-01 09:00',
        'end_date' => '2026-12-05 18:00',
        'registration_open_at' => '2026-10-01 09:00',
        'registration_close_at' => '2026-11-20 18:00',
        'polling_start_date' => '2026-11-22 09:00',
        'polling_deadline' => '2026-11-25 17:00',
    ]);

    $bars = collect($this->actingAs(superAdmin(), 'admin')->getJson(route('admin.events.calendar-data'))->assertOk()->json())
        ->keyBy('id');

    expect($bars)->toHaveCount(3)
        ->and($bars["event-{$event->id}"]['end'])->toBe('2026-12-06')   // exclusive end
        ->and($bars["event-{$event->id}"]['color'])->toBe('#1E88E5')
        ->and($bars["reg-{$event->id}"]['backgroundColor'])->toBe(CalendarPalette::tint('#1E88E5'))
        ->and($bars["poll-{$event->id}"]['url'])->toBe(route('admin.polling.show', $event));

    $this->actingAs(superAdmin(), 'admin')->get(route('admin.events.calendar'))->assertOk();
});

it('shows a state admin what the list shows them: no drafts', function () {
    Event::factory()->create(['status' => 'draft', 'title' => 'Hidden']);
    Event::factory()->create(['status' => 'open', 'title' => 'Shown']);

    $titles = collect($this->actingAs(stateAdmin('Johor'), 'admin')->getJson(route('admin.events.calendar-data'))->json())
        ->pluck('title');

    expect($titles)->toContain('Shown')->not->toContain('Hidden');
});

it('saves a calendar colour from the list, and refuses one that is not on it', function () {
    $event = Event::factory()->create();
    $form = fn ($colour) => [
        'title' => $event->title, 'status' => 'draft', 'location' => 'L', 'state' => null,
        'start_date' => '2026-12-01T09:00', 'end_date' => '2026-12-02T09:00',
        'registration_open_at' => '2026-10-01T09:00', 'registration_close_at' => '2026-11-01T09:00',
        'max_participants' => 10, 'colour' => $colour,
    ];

    $this->actingAs(superAdmin(), 'admin')->put(route('admin.events.update', $event), $form('#00897B'))->assertRedirect();
    expect($event->fresh()->colour)->toBe('#00897B');

    $this->actingAs(superAdmin(), 'admin')->put(route('admin.events.update', $event), $form('#123456'))->assertSessionHasErrors('colour');
});
