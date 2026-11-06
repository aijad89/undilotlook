<?php

use App\Models\Company;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\KrafBidang;

/**
 * A company's usual craft code is set by an admin on its page, never shown to
 * the participant, and filled in when the company is added to a draw list —
 * unless the admin picks a code for the batch there, which wins.
 */
it('saves an active code from the company page, and clears it', function () {
    KrafBidang::factory()->code('TBP')->create();
    $company = Company::factory()->create(['state' => 'Johor']);
    $admin = stateAdmin('Johor');

    $this->actingAs($admin, 'admin')
        ->put(route('admin.companies.kraf-code.update', $company), ['kraf_code' => 'TBP'])
        ->assertRedirect()->assertSessionHas('success');
    expect($company->fresh()->kraf_code)->toBe('TBP');

    $this->actingAs($admin, 'admin')->get(route('admin.companies.edit', $company))
        ->assertOk()->assertSee('Craft category code');

    $this->actingAs($admin, 'admin')->put(route('admin.companies.kraf-code.update', $company), ['kraf_code' => '']);
    expect($company->fresh()->kraf_code)->toBeNull();
});

it('refuses a code that is not on the coding sheet, or retired', function () {
    KrafBidang::factory()->code('OLD')->create(['is_active' => false]);
    $company = Company::factory()->create(['state' => 'Johor']);

    foreach (['NOPE', 'OLD'] as $code) {
        $this->actingAs(superAdmin(), 'admin')
            ->put(route('admin.companies.kraf-code.update', $company), ['kraf_code' => $code])
            ->assertSessionHasErrors('kraf_code');
    }
    expect($company->fresh()->kraf_code)->toBeNull();
});

it('keeps a state admin to their own zone', function () {
    KrafBidang::factory()->code('TBP')->create();
    $company = Company::factory()->create(['state' => 'Melaka']);

    $this->actingAs(stateAdmin('Johor'), 'admin')
        ->put(route('admin.companies.kraf-code.update', $company), ['kraf_code' => 'TBP'])
        ->assertForbidden();
    expect($company->fresh()->kraf_code)->toBeNull();
});

it('fills each company\'s own code in on the draw list, unless one is picked there', function () {
    $event = Event::factory()->create();
    $batik = Company::factory()->create(['state' => 'Johor', 'kraf_code' => 'TBP']);
    $kayu = Company::factory()->create(['state' => 'Johor', 'kraf_code' => 'RKP']);
    $none = Company::factory()->create(['state' => 'Johor']);
    $admin = stateAdmin('Johor');

    $this->actingAs($admin, 'admin')->get(route('admin.events.registrations.index', $event))
        ->assertOk()->assertSee('[TBP]');

    $this->actingAs($admin, 'admin')->post(route('admin.events.registrations.store', $event), [
        'company_id' => [$batik->id, $kayu->id, $none->id],
    ]);

    $codes = EventRegistration::pluck('code', 'company_id');
    expect($codes[$batik->id])->toBe('TBP')
        ->and($codes[$kayu->id])->toBe('RKP')
        ->and($codes[$none->id])->toBeNull();

    // A code picked for the batch wins over the company's own.
    $other = Event::factory()->create();
    $this->actingAs($admin, 'admin')->post(route('admin.events.registrations.store', $other), [
        'company_id' => [$batik->id], 'code' => 'TBPS',
    ]);
    expect(EventRegistration::where('event_id', $other->id)->sole()->code)->toBe('TBPS');
});

it('never shows the code to the participant', function () {
    $company = Company::factory()->create(['kraf_code' => 'ZZQX']);

    $this->actingAs($company->user, 'web')->get(route('dashboard'))
        ->assertOk()->assertDontSee('ZZQX');
});

it('saves a booth size beside the code, and refuses one not on the list', function () {
    $company = Company::factory()->create(['state' => 'Johor']);
    $admin = stateAdmin('Johor');

    $this->actingAs($admin, 'admin')
        ->put(route('admin.companies.kraf-code.update', $company), ['plot_size' => '3x6'])
        ->assertSessionHas('success');
    expect($company->fresh()->plot_size)->toBe('3x6');

    $this->actingAs($admin, 'admin')
        ->put(route('admin.companies.kraf-code.update', $company), ['plot_size' => '99x99'])
        ->assertSessionHasErrors('plot_size');
    expect($company->fresh()->plot_size)->toBe('3x6');
});

it('fills each company\'s own booth size in on the draw list, else 3x3, unless one is picked there', function () {
    $event = Event::factory()->create();
    $big = Company::factory()->create(['state' => 'Johor', 'plot_size' => '3x6']);
    $plain = Company::factory()->create(['state' => 'Johor']);
    $admin = stateAdmin('Johor');

    $this->actingAs($admin, 'admin')->post(route('admin.events.registrations.store', $event), [
        'company_id' => [$big->id, $plain->id],
    ]);

    $sizes = EventRegistration::pluck('plot_size', 'company_id');
    expect($sizes[$big->id])->toBe('3x6')->and($sizes[$plain->id])->toBe('3x3');

    $other = Event::factory()->create();
    $this->actingAs($admin, 'admin')->post(route('admin.events.registrations.store', $other), [
        'company_id' => [$big->id], 'plot_size' => '3x9',
    ]);
    expect(EventRegistration::where('event_id', $other->id)->sole()->plot_size)->toBe('3x9');
});
