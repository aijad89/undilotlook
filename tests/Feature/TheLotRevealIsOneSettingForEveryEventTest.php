<?php

use App\Models\Event;

/**
 * The lot reveal is one national choice in Setting › Lot Draw, used by every
 * event; only super and HQ may change it.
 */
it('saves one reveal for every event, and falls back to the split-flap board', function () {
    expect(Event::lotReveal())->toBe('splitflap');

    $this->actingAs(superAdmin(), 'admin')
        ->put(route('admin.settings.lot-draw.update'), ['draw_animation' => 'envelope'])
        ->assertSessionHas('success');

    expect(Event::lotReveal())->toBe('envelope');

    $this->actingAs(superAdmin(), 'admin')->get(route('admin.settings.lot-draw'))
        ->assertOk()->assertSee('<option value="envelope" selected>', false);
});

it('refuses an unknown reveal, and a state admin', function () {
    $this->actingAs(superAdmin(), 'admin')
        ->put(route('admin.settings.lot-draw.update'), ['draw_animation' => 'fireworks'])
        ->assertSessionHasErrors('draw_animation');

    auth()->forgetGuards();
    $this->actingAs(stateAdmin('Johor'), 'admin')
        ->put(route('admin.settings.lot-draw.update'), ['draw_animation' => 'envelope'])
        ->assertForbidden();

    expect(Event::lotReveal())->toBe('splitflap');

    // A stored value no longer on the list must not break the Confirm page.
    \App\Models\Setting::set(Event::DRAW_ANIMATION_SETTING, 'retired-style');
    expect(Event::lotReveal())->toBe('splitflap');
});

it('plays the chosen reveal on the participant Confirm page', function () {
    \App\Models\Setting::set(Event::DRAW_ANIMATION_SETTING, 'envelope');

    $event = Event::factory()->create([
        'status' => 'open', 'polling_status' => 'polling',
        'polling_start_date' => now()->subDay(), 'polling_deadline' => now()->addDay(),
    ]);
    \App\Models\Plot::factory()->create(['event_id' => $event->id]);
    $entry = \App\Models\EventRegistration::factory()->create(['event_id' => $event->id, 'code' => 'RKP']);
    $user = $entry->company->user;

    // The real flow: Confirm draws the lot, the next page reveals it.
    $this->actingAs($user, 'web')->post(route('zvote.submit', $event), ['agreement' => '1'])
        ->assertSessionHas('just_drawn');

    $this->actingAs($user, 'web')
        ->withSession(['just_drawn' => true])
        ->get(route('zvote.index', $event))
        ->assertOk()
        // The element's class, not the bare word: the reveal's own stylesheet names both styles.
        ->assertSee('class="lot-reveal lot-reveal--envelope"', false);
});
