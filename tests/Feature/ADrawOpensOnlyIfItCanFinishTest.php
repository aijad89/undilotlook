<?php

use App\Models\Company;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\KrafBidang;
use App\Models\Plot;
use App\Models\PlotSize;
use App\Support\PollingGuard;

/**
 * PollingGuard answers "can this draw finish?" before it opens. Each rule
 * refuses alone, so each is held alone, beside the one case that must pass.
 */
function guardedEvent(): array
{
    $event = Event::factory()->create(['polling_start_date' => now()->addDay(), 'polling_deadline' => now()->addDays(2)]);
    $rkp = KrafBidang::factory()->code('RKP')->create();
    $size = PlotSize::factory()->create(['event_id' => $event->id, 'size' => '3x3']);

    return [$event, $rkp, $size];
}

function drawEntry(Event $event, ?string $code = 'RKP', ?string $size = '3x3', array $extra = []): EventRegistration
{
    return EventRegistration::factory()->create($extra + [
        'event_id' => $event->id,
        'company_id' => Company::factory(),
        'code' => $code,
        'plot_size' => $size,
    ]);
}

it('opens when every entry has a code, an account, and a lot of its size', function () {
    [$event, $rkp, $size] = guardedEvent();
    Plot::factory()->count(2)->on($size)->coded($rkp)->create();
    drawEntry($event);
    drawEntry($event);

    expect(PollingGuard::blocker($event))->toBeNull();
});

it('refuses an event with no plots', function () {
    [$event] = guardedEvent();
    drawEntry($event);

    expect(PollingGuard::blocker($event))->toContain('no plots');
});

it('refuses a row HQ has not verified yet', function () {
    [$event, $rkp, $size] = guardedEvent();
    Plot::factory()->on($size)->coded($rkp)->create();
    drawEntry($event, 'RKP', '3x3', ['verified_at' => null]);

    expect(PollingGuard::blocker($event))->toContain('waiting for HQ');
});

it('refuses a company in the draw that has no account yet', function () {
    [$event, $rkp, $size] = guardedEvent();
    Plot::factory()->on($size)->coded($rkp)->create();
    drawEntry($event, 'RKP', '3x3', ['user_id' => null]);

    expect(PollingGuard::blocker($event))->toContain('no participant account');
});

it('refuses a blank code, empty string included', function (?string $blank) {
    [$event, $rkp, $size] = guardedEvent();
    Plot::factory()->count(2)->on($size)->coded($rkp)->create();
    drawEntry($event, $blank);

    expect(PollingGuard::blocker($event))->toContain('no craft code');
})->with([null, '']);

it('refuses a code with no plot, by name, unless the open pool can absorb it', function () {
    [$event, $rkp, $size] = guardedEvent();
    Plot::factory()->on($size)->coded($rkp)->create();
    drawEntry($event, 'RKP');
    drawEntry($event, 'TBPS');

    expect(PollingGuard::blocker($event))->toContain('TBPS');

    Plot::factory()->on($size)->create();

    expect(PollingGuard::blocker($event))->toBeNull();
});

it('counts the open pool per size, so a pool of the wrong size absorbs nothing', function () {
    [$event, $rkp, $size] = guardedEvent();
    $big = PlotSize::factory()->create(['event_id' => $event->id, 'size' => '3x6']);

    Plot::factory()->on($size)->coded($rkp)->create();
    Plot::factory()->on($big)->create();
    drawEntry($event, 'RKP', '3x3');
    drawEntry($event, 'RKP', '3x3');

    expect(PollingGuard::blocker($event))->toContain('RKP 3x3 (need 2, have 1)');

    Plot::factory()->on($size)->create();

    expect(PollingGuard::blocker($event))->toBeNull();
});

it('ignores a company already holding a lot, whatever its code', function () {
    [$event, $rkp, $size] = guardedEvent();
    $held = Plot::factory()->on($size)->create(['is_allocated' => true]);
    Plot::factory()->on($size)->coded($rkp)->create();

    drawEntry($event, 'RKP');
    drawEntry($event, 'TBPS', '3x3', ['plot_id' => $held->id]);

    expect(PollingGuard::blocker($event))->toBeNull();
});

it('refuses a draw with no end date, which every participant screen names', function () {
    [$event, $rkp, $size] = guardedEvent();
    Plot::factory()->count(2)->on($size)->coded($rkp)->create();
    drawEntry($event);
    $event->update(['polling_deadline' => null]);

    expect(PollingGuard::blocker($event))->toContain('no end date');
});
