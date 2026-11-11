<?php

use App\Models\Company;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Plot;

/**
 * A company's booth size and its draw-list rows move together, whichever one is
 * changed — but only in events whose lot draw has not started, and never on a
 * row that already holds a lot.
 */
function onDrawList(Company $company, array $event = [], array $row = []): EventRegistration
{
    return EventRegistration::factory()->create($row + [
        'event_id' => Event::factory()->create($event)->id,
        'company_id' => $company->id,
        'plot_size' => '3x3',
    ]);
}

it('carries a size set on the company page to its draw lists that have not started', function () {
    $company = Company::factory()->create(['state' => 'Kedah', 'plot_size' => '3x3']);
    $notStarted = onDrawList($company);
    $drawOpen = onDrawList($company, ['polling_status' => 'polling']);
    $holdsLot = onDrawList($company, [], ['plot_id' => Plot::factory()->create()->id]);

    $this->actingAs(superAdmin(), 'admin')
        ->put(route('admin.companies.kraf-code.update', $company), ['plot_size' => '3x6'])
        ->assertSessionHas('success');

    expect($notStarted->fresh()->plot_size)->toBe('3x6')
        ->and($drawOpen->fresh()->plot_size)->toBe('3x3')
        ->and($holdsLot->fresh()->plot_size)->toBe('3x3');
});

it('carries a size changed on one draw-list row to the company and its other draw lists', function () {
    $company = Company::factory()->create(['state' => 'Kedah', 'plot_size' => '3x3']);
    $edited = onDrawList($company);
    $other = onDrawList($company);

    $this->actingAs(superAdmin(), 'admin')
        ->put(route('admin.events.registrations.update', [$edited->event_id, $edited]), ['plot_size' => '3x6'])
        ->assertSessionHas('code_success');

    expect($company->fresh()->plot_size)->toBe('3x6')
        ->and($edited->fresh()->plot_size)->toBe('3x6')
        ->and($other->fresh()->plot_size)->toBe('3x6');
});

it('carries a size set in bulk, or picked when adding, to each company', function () {
    $a = Company::factory()->create(['state' => 'Kedah', 'plot_size' => '3x3']);
    $b = Company::factory()->create(['state' => 'Kedah', 'plot_size' => '3x3']);
    $rowA = onDrawList($a);
    $admin = superAdmin();

    $this->actingAs($admin, 'admin')->post(route('admin.events.registrations.bulk', $rowA->event_id), [
        'action' => 'set_size', 'registration_id' => [$rowA->id], 'plot_size' => '3x9',
    ])->assertSessionHas('success');
    expect($a->fresh()->plot_size)->toBe('3x9');

    $this->actingAs($admin, 'admin')->post(route('admin.events.registrations.store', Event::factory()->create()), [
        'company_id' => [$b->id], 'plot_size' => '3x6',
    ]);
    expect($b->fresh()->plot_size)->toBe('3x6');
});
