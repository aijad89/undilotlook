<?php

use App\Models\AuditTrail;
use App\Models\Company;
use App\Models\Event;
use App\Models\EventRegistration;

/**
 * The audit trail screen: super and hq read every row, including the cascade
 * summaries and the plan's hand-written rows; a state admin reads none.
 */
it('lists what happened and opens a cascade summary in readable form', function () {
    $event = Event::factory()->create(['title' => 'Deleted Fair']);
    EventRegistration::factory()->count(2)->create(['event_id' => $event->id]);
    $event->delete();

    $row = AuditTrail::where('action', 'deleted')->where('auditable_type', Event::class)->sole();
    $hq = App\Models\Admin::factory()->hq()->create();

    $this->actingAs($hq, 'admin')->get(route('admin.audit-trails.index', ['model' => 'Event']))
        ->assertOk()
        ->assertSee(route('admin.audit-trails.show', $row));

    $this->actingAs($hq, 'admin')->get(route('admin.audit-trails.show', $row))
        ->assertOk()
        ->assertSee('event_registrations');
});

it('keeps the audit trail away from a state admin', function () {
    $row = AuditTrail::record('created', Company::factory()->create());

    $this->actingAs(stateAdmin('Johor'), 'admin')->get(route('admin.audit-trails.index'))->assertForbidden();
    $this->actingAs(stateAdmin('Johor'), 'admin')->get(route('admin.audit-trails.show', $row))->assertForbidden();
});
