<?php

use App\Exceptions\LotUnavailable;
use App\Models\Company;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\KrafBidang;
use App\Models\Plot;
use App\Models\PlotSize;
use App\Models\Poll;
use App\Services\LotAllocator;
use App\Services\PlotAllocationAlgorithm;

/**
 * The participant's Confirm button and the admin's bulk run both seat people
 * through LotAllocator. In `daftar` they once held separate copies of the rules
 * and the copies drifted — these hold the one copy to what both doors promise.
 */
function drawEvent(): Event
{
    return Event::factory()->create(['polling_status' => 'polling']);
}

function entrant(Event $event, ?string $code = 'RKP', ?string $size = '3x3', array $extra = []): EventRegistration
{
    return EventRegistration::factory()->create($extra + [
        'event_id' => $event->id,
        'company_id' => Company::factory(),
        'code' => $code,
        'plot_size' => $size,
    ]);
}

it('draws a lot of the registration own code when one is free', function () {
    $event = drawEvent();
    $rkp = KrafBidang::factory()->code('RKP')->create();
    $tbps = KrafBidang::factory()->code('TBPS')->create();
    $size = PlotSize::factory()->create(['event_id' => $event->id]);

    Plot::factory()->on($size)->coded($tbps)->create();
    $mine = Plot::factory()->on($size)->coded($rkp)->create();

    $plot = (new LotAllocator)->assign(entrant($event));

    expect($plot->id)->toBe($mine->id)
        ->and($plot->fresh()->allocation_type)->toBe('algorithm');
});

it('gives an unmatched code the open pool, never another code lot', function () {
    $event = drawEvent();
    $tbps = KrafBidang::factory()->code('TBPS')->create();
    $size = PlotSize::factory()->create(['event_id' => $event->id]);

    $theirs = Plot::factory()->on($size)->coded($tbps)->create();
    $open = Plot::factory()->on($size)->create();

    $plot = (new LotAllocator)->assign(entrant($event, 'RKP'));

    expect($plot->id)->toBe($open->id)
        ->and($theirs->fresh()->is_allocated)->toBeFalse();

    // With the open pool gone, the other code's lot is still not on offer.
    expect(fn () => (new LotAllocator)->assign(entrant($event, 'RKP')))
        ->toThrow(LotUnavailable::class);
});

it('treats a plot tagged to a craft category, which no code can claim, as open pool', function () {
    $event = drawEvent();
    $category = KrafBidang::factory()->create();
    $size = PlotSize::factory()->create(['event_id' => $event->id]);

    $plot = Plot::factory()->on($size)->create(['bidang_id' => $category->id]);

    expect((new LotAllocator)->assign(entrant($event, 'RKP'))->id)->toBe($plot->id);
});

it('holds a registration to its booth size, in the code and in the open pool', function () {
    $event = drawEvent();
    $rkp = KrafBidang::factory()->code('RKP')->create();
    $small = PlotSize::factory()->create(['event_id' => $event->id, 'size' => '3x3']);
    $big = PlotSize::factory()->create(['event_id' => $event->id, 'size' => '3x6']);

    Plot::factory()->on($small)->coded($rkp)->create();
    Plot::factory()->on($small)->create();
    $wanted = Plot::factory()->on($big)->create();

    $plot = (new LotAllocator)->assign(entrant($event, 'RKP', '3x6'));

    expect($plot->id)->toBe($wanted->id);

    try {
        (new LotAllocator)->assign(entrant($event, 'RKP', '3x6'));
        $this->fail('A second 3x6 entrant was seated with no 3x6 lot left.');
    } catch (LotUnavailable $e) {
        expect($e->reason)->toBe('size_exhausted');
    }
});

it('hands a preset holder the lot set aside for them, once', function () {
    $event = drawEvent();
    $reserved = Plot::factory()->create(['event_id' => $event->id]);
    $registration = entrant($event, 'RKP', '3x3', ['plot_id' => $reserved->id]);

    // Exactly what a preset reservation writes: the same state a drawn lot is in.
    $reserved->update([
        'company_id' => $registration->company_id,
        'allocated_to' => $registration->user_id,
        'is_allocated' => true,
        'allocation_type' => 'preset',
        'is_locked' => true,
    ]);

    $plot = (new LotAllocator)->assign($registration);

    expect($plot->id)->toBe($reserved->id)
        ->and($plot->fresh()->allocation_type)->toBe('preset')
        ->and(Poll::count())->toBe(1);

    try {
        (new LotAllocator)->assign($registration->fresh());
        $this->fail('A preset holder drew twice.');
    } catch (LotUnavailable $e) {
        expect($e->reason)->toBe('already_drawn');
    }
});

it('reports a reservation somebody else now holds as missing', function () {
    $event = drawEvent();
    $reserved = Plot::factory()->create([
        'event_id' => $event->id,
        'is_allocated' => true,
        'allocated_to' => Company::factory()->create()->user_id,
    ]);

    try {
        (new LotAllocator)->assign(entrant($event, 'RKP', '3x3', ['plot_id' => $reserved->id]));
        $this->fail('Took a lot reserved for somebody else.');
    } catch (LotUnavailable $e) {
        expect($e->reason)->toBe('preset_missing');
    }
});

it('seats everyone on the bulk run, writes a Poll each, and marks the draw done', function () {
    $event = drawEvent();
    $rkp = KrafBidang::factory()->code('RKP')->create();
    $size = PlotSize::factory()->create(['event_id' => $event->id]);
    Plot::factory()->count(3)->on($size)->coded($rkp)->create();

    // confirmed_participation is false on all three: it is what the draw sets,
    // never what it requires.
    entrant($event);
    entrant($event);
    entrant($event);

    $result = (new PlotAllocationAlgorithm)->run($event);

    expect($result)->toBe(['allocated' => 3, 'waiting_list' => []])
        ->and(Poll::count())->toBe(3)
        ->and(EventRegistration::whereNull('plot_id')->count())->toBe(0)
        ->and(EventRegistration::where('confirmed_participation', true)->count())->toBe(3)
        ->and($event->fresh()->polling_status)->toBe('completed');
});

it('names who it could not seat, keeps everyone it did, and leaves the draw open', function () {
    $event = drawEvent();
    $rkp = KrafBidang::factory()->code('RKP')->create();
    $size = PlotSize::factory()->create(['event_id' => $event->id]);
    Plot::factory()->on($size)->coded($rkp)->create();

    entrant($event);
    $noAccount = entrant($event, 'RKP', '3x3', ['user_id' => null]);
    $noLot = entrant($event, 'RKP', '3x6');

    $result = (new PlotAllocationAlgorithm)->run($event);

    // The account-less company must not roll back the lot already handed out.
    expect($result['allocated'])->toBe(1)
        ->and($result['waiting_list'])->toEqualCanonicalizing([
            $noAccount->company->name,
            $noLot->company->name,
        ])
        ->and(Plot::where('is_allocated', true)->count())->toBe(1)
        ->and($event->fresh()->polling_status)->toBe('polling');
});
