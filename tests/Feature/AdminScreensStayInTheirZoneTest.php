<?php

use App\Models\Company;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\KrafBidang;

/**
 * A state admin's zone is the whole of their scope, and nothing in the URL bar
 * should get them past it.
 *
 * There is no middleware that enforces this — deliberately, because one on every
 * admin route would READ like enforcement while enforcing nothing. Each screen
 * scopes its own query and refuses its own ids, so each screen is tested.
 */
it('lists only companies from the admin own zone, and refuses the others by id', function () {
    $mine = Company::factory()->create(['state' => 'Johor', 'name' => 'Kraf Johor']);
    $theirs = Company::factory()->create(['state' => 'Kedah', 'name' => 'Kraf Kedah']);

    $this->actingAs(stateAdmin('Johor'), 'admin')
        ->get(route('admin.companies.index'))
        ->assertOk()
        ->assertSee('Kraf Johor')
        ->assertDontSee('Kraf Kedah');

    $this->actingAs(stateAdmin('Johor'), 'admin')
        ->get(route('admin.companies.edit', $theirs))
        ->assertForbidden();

    $this->actingAs(stateAdmin('Johor'), 'admin')
        ->get(route('admin.companies.edit', $mine))
        ->assertOk();
});

it('refuses a state admin writing into another zone, even when they post the zone', function () {
    $this->actingAs(stateAdmin('Johor'), 'admin')
        ->post(route('admin.companies.store'), [
            'name' => 'Kraf Elsewhere',
            'state' => 'Kedah',
            'owner_name' => 'Owner',
            'email' => 'owner@example.test',
        ])
        ->assertSessionHasErrors('state');

    expect(Company::where('name', 'Kraf Elsewhere')->exists())->toBeFalse();
});

/**
 * A draft is hidden even with this admin's participants in it; an open event is
 * shown even with none, because the state admin is who fills it.
 */
it('hides a draft event from a state admin even when their own company is in its draw', function () {
    $event = Event::factory()->draft()->create(['title' => 'Still A Draft']);
    $company = Company::factory()->create(['state' => 'Johor']);
    EventRegistration::factory()->create(['event_id' => $event->id, 'company_id' => $company->id]);

    $admin = stateAdmin('Johor');

    $this->actingAs($admin, 'admin')
        ->get(route('admin.events.index'))
        ->assertOk()
        ->assertDontSee('Still A Draft');

    $this->actingAs($admin, 'admin')
        ->get(route('admin.events.registrations.index', $event))
        ->assertForbidden();
});

it('opens an event with no company of theirs to a state admin, showing none of its rows', function () {
    $event = Event::factory()->create(['title' => 'Kedah Only']);
    $company = Company::factory()->create(['state' => 'Kedah', 'name' => 'Kedah Carver']);
    EventRegistration::factory()->create(['event_id' => $event->id, 'company_id' => $company->id]);

    $admin = stateAdmin('Johor');

    $this->actingAs($admin, 'admin')
        ->get(route('admin.events.index'))
        ->assertOk()
        ->assertSee('Kedah Only');

    $this->actingAs($admin, 'admin')
        ->get(route('admin.events.registrations.index', $event))
        ->assertOk()
        ->assertDontSee('Kedah Carver');
});

it('shows a state admin the draw list, with their own zone rows only', function () {
    $event = Event::factory()->create();

    $mine = Company::factory()->create(['state' => 'Johor', 'name' => 'Johor Weaver']);
    $theirs = Company::factory()->create(['state' => 'Kedah', 'name' => 'Kedah Carver']);

    EventRegistration::factory()->create(['event_id' => $event->id, 'company_id' => $mine->id]);
    EventRegistration::factory()->create(['event_id' => $event->id, 'company_id' => $theirs->id]);

    $this->actingAs(stateAdmin('Johor'), 'admin')
        ->get(route('admin.events.registrations.index', $event))
        ->assertOk()
        ->assertSee('Johor Weaver')
        ->assertDontSee('Kedah Carver');
});

it('lets super and hq write events while a state admin may only read the list', function () {
    $event = Event::factory()->create();

    $this->actingAs(stateAdmin('Johor'), 'admin')
        ->get(route('admin.events.create'))
        ->assertForbidden();

    $this->actingAs(stateAdmin('Johor'), 'admin')
        ->delete(route('admin.events.destroy', $event))
        ->assertForbidden();

    $this->actingAs(superAdmin(), 'admin')
        ->get(route('admin.events.create'))
        ->assertOk();
});

it('keeps the national lists away from a state admin', function () {
    $admin = stateAdmin('Johor');

    $this->actingAs($admin, 'admin')->get(route('admin.settings.lists'))->assertForbidden();
    $this->actingAs($admin, 'admin')->get(route('admin.settings.kraf-codes'))->assertForbidden();
    $this->actingAs($admin, 'admin')->get(route('admin.settings.kraf-hierarchy'))->assertForbidden();

    $this->actingAs(superAdmin(), 'admin')->get(route('admin.settings.lists'))->assertOk();
});

it('keeps zones super-only, even for hq, because zones decide who sees what', function () {
    $hq = App\Models\Admin::factory()->hq()->create();

    $this->actingAs($hq, 'admin')
        ->put(route('admin.settings.zones.rename'), ['from' => 'Johor', 'to' => 'Johor Baharu'])
        ->assertForbidden();

    expect(App\Support\AdminZones::has('Johor'))->toBeTrue();
});

/**
 * The settings screens are the two halves of `kraf_bidangs`, and each has to show
 * its own half only. A screen that forgets lists both lists at once.
 */
it('shows the coding sheet on one screen and the hierarchy on the other', function () {
    KrafBidang::factory()->create(['name' => 'TEKSTIL']);
    KrafBidang::factory()->code('TBPS')->create(['name' => 'TENUNAN']);

    $admin = superAdmin();

    $this->actingAs($admin, 'admin')
        ->get(route('admin.settings.kraf-codes'))
        ->assertOk()
        ->assertSee('TENUNAN')
        ->assertDontSee('TEKSTIL');

    $this->actingAs($admin, 'admin')
        ->get(route('admin.settings.kraf-hierarchy'))
        ->assertOk()
        ->assertSee('TEKSTIL')
        // The coded row appears only as something a sub-category may be MAPPED to,
        // never as a category of its own.
        ->assertDontSee('<h3 class="mac-card-title">TENUNAN</h3>', false);
});

it('counts only the admin own zone on the event list', function () {
    $event = Event::factory()->create(['title' => 'Shared Fair']);
    EventRegistration::factory()->create(['event_id' => $event->id, 'company_id' => Company::factory()->create(['state' => 'Johor'])->id]);
    foreach (Company::factory()->count(2)->create(['state' => 'Kedah']) as $kedah) {
        EventRegistration::factory()->create(['event_id' => $event->id, 'company_id' => $kedah->id]);
    }

    $count = fn ($admin) => $this->actingAs($admin, 'admin')->get(route('admin.events.index'))
        ->assertOk()->viewData('events')->firstWhere('id', $event->id)->registrations_count;

    expect($count(stateAdmin('Johor')))->toBe(1)
        ->and($count(superAdmin()))->toBe(3);
});
