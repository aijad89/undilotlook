<?php

use App\Models\Company;
use App\Models\CompanyKraf;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\KrafBidang;
use App\Models\KrafSubBidang;

/**
 * The admin dashboard: what needs fixing, the events, the companies, and
 * shortcuts. A state admin's counts are their own zone's and never include a
 * draft event; HQ and super count everything.
 */
function registered(Event $event, string $state, array $attributes = []): EventRegistration
{
    return EventRegistration::factory()->create($attributes + [
        'event_id' => $event->id,
        'company_id' => Company::factory()->create(['state' => $state])->id,
    ]);
}

it('counts draw-list rows with no code, in the admin\'s own zone only', function () {
    $event = Event::factory()->create(['title' => 'MICF Test']);
    registered($event, 'Perak', ['code' => null]);
    registered($event, 'Perak', ['code' => null]);
    registered($event, 'Perak', ['code' => 'TBP']);
    registered($event, 'Johor', ['code' => null]);

    $this->actingAs(stateAdmin('Perak'), 'admin')->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('MICF Test: 2 draw-list rows with no craft code')
        ->assertSee('3 on draw list');

    auth()->forgetGuards();
    $this->actingAs(superAdmin(), 'admin')->get(route('admin.dashboard'))
        ->assertSee('MICF Test: 3 draw-list rows with no craft code')
        ->assertSee('4 on draw list');
});

it('hides draft events from a state admin, and shows them to HQ and super', function () {
    Event::factory()->create(['title' => 'Secret Draft', 'status' => 'draft']);

    $this->actingAs(stateAdmin('Perak'), 'admin')->get(route('admin.dashboard'))->assertDontSee('Secret Draft');

    auth()->forgetGuards();
    $this->actingAs(superAdmin(), 'admin')->get(route('admin.dashboard'))->assertSee('Secret Draft');
});

it('flags companies with no craft category or no craft code, linking to the filtered list', function () {
    $category = KrafBidang::factory()->create(['name' => 'TEKSTIL']);
    $described = Company::factory()->create(['state' => 'Perak', 'kraf_code' => 'TBP', 'name' => 'Described Co']);
    CompanyKraf::factory()->create([
        'company_id' => $described->id, 'bidang_id' => $category->id,
        'sub_bidang_id' => KrafSubBidang::factory()->create(['bidang_id' => $category->id])->id,
    ]);
    Company::factory()->create(['state' => 'Perak', 'name' => 'Bare Co']);
    Company::factory()->create(['state' => 'Johor']);
    $admin = stateAdmin('Perak');

    $this->actingAs($admin, 'admin')->get(route('admin.dashboard'))
        ->assertSee('1 company with no craft category')
        ->assertSee('1 company with no craft code')
        ->assertSee(route('admin.companies.index', ['missing' => 'category']), false);

    $this->actingAs($admin, 'admin')->get(route('admin.companies.index', ['missing' => 'category']))
        ->assertSee('Bare Co')->assertDontSee('Described Co');
    $this->actingAs($admin, 'admin')->get(route('admin.companies.index', ['missing' => 'code']))
        ->assertSee('Bare Co')->assertDontSee('Described Co');
});

it('says all clear when nothing needs fixing', function () {
    $this->actingAs(superAdmin(), 'admin')->get(route('admin.dashboard'))
        ->assertOk()->assertSee('All clear');
});

it('shows draw progress for an open draw, and who is still waiting', function () {
    $event = Event::factory()->create(['title' => 'Open Draw', 'polling_status' => 'polling', 'polling_deadline' => now()->addDays(2)]);
    registered($event, 'Perak', ['code' => 'TBP', 'confirmed_participation' => true]);
    registered($event, 'Perak', ['code' => 'TBP']);

    $this->actingAs(superAdmin(), 'admin')->get(route('admin.dashboard'))
        ->assertSee('50% confirmed')
        ->assertSee('Open Draw: 1 company not confirmed yet');
});

it('gives each role only the shortcuts it may use, and zones only to HQ and super', function () {
    Company::factory()->create(['state' => 'Kedah']);

    $newEvent = route('admin.events.create');
    $announce = route('admin.announcements.create');

    $this->actingAs(stateAdmin('Perak'), 'admin')->get(route('admin.dashboard'))
        ->assertSee(route('admin.companies.create'), false)
        ->assertDontSee($newEvent, false)->assertDontSee($announce, false)->assertDontSee('By zone');

    auth()->forgetGuards();
    $this->actingAs(superAdmin(), 'admin')->get(route('admin.dashboard'))
        ->assertSee($newEvent, false)->assertSee($announce, false)->assertSee('By zone')->assertSee('Kedah');
});
