<?php

use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Plot;

/**
 * A lot the office sets aside is a surprise: the company and its zone admin
 * see nothing until the company confirms, and then it reads as drawn.
 * Only super and HQ ever see "Preset".
 */
function setAside(): array
{
    $event = Event::factory()->create([
        'status' => 'open', 'polling_status' => 'polling',
        'polling_start_date' => now()->subDay(), 'polling_deadline' => now()->addDay(),
    ]);
    $plot = Plot::factory()->create(['event_id' => $event->id, 'plot_code' => 'HALL Z-SECRET7']);
    $entry = EventRegistration::factory()->create(['event_id' => $event->id, 'code' => 'RKP']);
    $entry->company->update(['state' => 'Johor']);

    test()->actingAs(superAdmin(), 'admin')->post(route('admin.polling.preset-assign', $event), [
        'plot_id' => $plot->id, 'registration_id' => $entry->id,
    ])->assertSessionHas('success');
    auth()->forgetGuards();

    return [$event, $entry];
}

function zoneScreens(Event $event): array
{
    $as = test()->actingAs(stateAdmin('Johor'), 'admin');

    return [
        'lot draw' => $as->get(route('admin.polling.show', $event))->assertOk()->getContent(),
        'draw list' => $as->get(route('admin.events.registrations.index', $event))->assertOk()->getContent(),
        'lot results csv' => $as->get(route('admin.reports.lot-draw'))->assertOk()->streamedContent(),
        'draw lists csv' => $as->get(route('admin.reports.registrations'))->assertOk()->streamedContent(),
    ];
}

it('hides a set-aside lot from the zone admin until the company confirms', function () {
    [$event] = setAside();

    $screens = zoneScreens($event);

    // The lot draw page lists every free plot as inventory, so the plot is
    // there — as free, and the company holds nothing.
    preg_match('/SECRET7<\/td>.*?<\/tr>/s', $screens['lot draw'], $row);
    expect($row[0])->toContain('Free')
        ->and($screens['lot draw'])->toContain('None yet')->not->toContain('Preset');
    unset($screens['lot draw']);

    foreach ($screens as $screen => $html) {
        expect(str_contains($html, 'SECRET7'))->toBeFalse("{$screen} shows the lot");
    }

    // The office still sees it, and what it is.
    $this->actingAs(superAdmin(), 'admin')->get(route('admin.polling.show', $event))
        ->assertSee('HALL Z-SECRET7')->assertSee('Preset');
});

it('shows it to the zone admin as drawn once the company confirms', function () {
    [$event, $entry] = setAside();

    $this->actingAs($entry->company->user, 'web')
        ->post(route('zvote.submit', $event), ['agreement' => '1'])
        ->assertSessionHas('just_drawn');
    auth()->forgetGuards();

    foreach (zoneScreens($event) as $screen => $html) {
        expect(str_contains($html, 'SECRET7'))->toBeTrue("{$screen} misses the lot")
            ->and(str_contains($html, 'Preset'))->toBeFalse("{$screen} says Preset");
    }
});

it('tells the company nothing before it confirms', function () {
    [$event, $entry] = setAside();

    $this->actingAs($entry->company->user, 'web')
        ->get(route('dashboard'))->assertOk()->assertDontSee('SECRET7');
    $this->actingAs($entry->company->user, 'web')
        ->get(route('zvote.index', $event))->assertOk()->assertDontSee('SECRET7');
});

it('leaves no trace in the zone admin counts or messages', function () {
    [$event, $entry] = setAside();
    $zone = stateAdmin('Johor');

    $this->actingAs($zone, 'admin')->get(route('admin.dashboard'))
        ->assertSee('0/1 plots taken');

    $summary = $this->actingAs($zone, 'admin')->get(route('admin.reports.event-summary'))->streamedContent();
    expect(str_getcsv(explode("\n", trim($summary))[1])[8])->toBe('0');

    $this->actingAs($zone, 'admin')
        ->delete(route('admin.events.registrations.destroy', [$event, $entry]))
        ->assertSessionHas('error', 'Only HQ can remove that company from the list now.');

    // The office is told plainly.
    auth()->forgetGuards();
    $this->actingAs(superAdmin(), 'admin')->get(route('admin.dashboard'))
        ->assertSee('1/1 plots taken');
});
