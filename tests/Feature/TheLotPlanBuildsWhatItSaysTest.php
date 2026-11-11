<?php

use App\Models\AuditTrail;
use App\Models\Company;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\KrafBidang;
use App\Models\Plot;
use App\Models\PlotSize;
use App\Models\Poll;

/**
 * The lot plan, from the two-page create form to the plots built from it, and
 * the stages the draw moves through after. Every page is rendered at least once
 * here, because every view on this screen is new.
 */
function planPost(Event $event, array $allocations = [], ?array $locations = null): array
{
    return [
        'event_id' => $event->id,
        'polling_start_date' => '2026-11-01T09:00',
        'polling_deadline' => '2026-11-05T17:00',
        'plot_sizes' => $locations ?? [hall('Hall A', ['3x3' => 5])],
        'bidang_allocations' => $allocations,
    ];
}

/** One location card as the form posts it: a name and its size lines. */
function hall(string $name, array $sizes): array
{
    return [
        'location' => $name,
        'sizes' => collect($sizes)->map(fn ($n, $size) => ['size' => $size, 'quantity' => $n])->values()->all(),
    ];
}

it('builds plots from a plan: coded where allocated, open pool for the rest', function () {
    $event = Event::factory()->create();
    $rkp = KrafBidang::factory()->code('RKP')->create();

    $this->actingAs(superAdmin(), 'admin')
        ->post(route('admin.polling.store'), planPost($event, [
            ['bidang_id' => $rkp->id, 'plot_size_index' => 0, 'size' => '3x6', 'plots_count' => 2],
        ], [hall('Hall A', ['3x3' => 3, '3x6' => 2])]))
        ->assertRedirect(route('admin.polling.show', $event));

    $event->refresh();

    expect($event->total_plots)->toBe(5)
        ->and($event->polling_status)->toBe('draft')
        ->and(Plot::where('bidang_id', $rkp->id)->pluck('plot_code')->sort()->values()->all())
            ->toBe(['HALL A-RKP1', 'HALL A-RKP2'])
        ->and(Plot::whereNull('bidang_id')->count())->toBe(3)
        ->and(PlotSize::where('event_id', $event->id)->pluck('quantity', 'size')->all())
            ->toEqual(['3x6' => 2, '3x3' => 3])
        ->and(AuditTrail::where('action', 'plots_generated')->value('new_values')['plots'])->toBe(5);
});

it('refuses a location with no booth size, or a size line with no size, which nobody could ever draw', function () {
    $event = Event::factory()->create();
    $admin = superAdmin();

    $this->actingAs($admin, 'admin')
        ->post(route('admin.polling.store'), planPost($event, [], [['location' => 'Hall A']]))
        ->assertSessionHasErrors('plot_sizes.0.sizes');

    $this->actingAs($admin, 'admin')
        ->post(route('admin.polling.store'), planPost($event, [], [['location' => 'Hall A', 'sizes' => [['quantity' => 2]]]]))
        ->assertSessionHasErrors('plot_sizes.0.sizes.0.size');

    expect(Plot::count())->toBe(0);
});

it('holds two booth sizes in one location, and reopens it as one card with both lines', function () {
    $event = Event::factory()->create();
    $rkp = KrafBidang::factory()->code('RKP')->create();
    $admin = superAdmin();

    $this->actingAs($admin, 'admin')->post(route('admin.polling.store'), planPost($event, [
        ['bidang_id' => $rkp->id, 'plot_size_index' => 0, 'size' => '3x6', 'plots_count' => 4],
    ], [hall('B', ['3x3' => 20, '3x6' => 10])]))->assertRedirect(route('admin.polling.show', $event));

    expect(PlotSize::where('event_id', $event->id)->pluck('quantity', 'size')->all())->toEqual(['3x3' => 20, '3x6' => 10])
        ->and(Plot::where('plot_code', 'like', 'B-RKP%')->count())->toBe(4)
        ->and(Plot::where('plot_code', 'like', 'B-3X6%')->count())->toBe(6)
        ->and(Plot::where('plot_code', 'like', 'B-3X3%')->count())->toBe(20);

    $html = $this->actingAs($admin, 'admin')->get(route('admin.polling.edit', $event))->assertOk()->getContent();

    expect($html)->toContain('name="plot_sizes[0][location]" class="mac-form-control plot-location" value="B"')
        ->not->toContain('name="plot_sizes[1][location]"')
        ->and($html)->toContain('name="plot_sizes[0][sizes][0][size]"')
        ->and($html)->toContain('name="plot_sizes[0][sizes][1][size]"')
        ->and($html)->toMatch('#name="plot_sizes\[0\]\[sizes\]\[1\]\[quantity\]"[^>]*value="10"#');
});

it('refuses an allocation at a size its location does not have', function () {
    $event = Event::factory()->create();
    $rkp = KrafBidang::factory()->code('RKP')->create();

    $this->actingAs(superAdmin(), 'admin')
        ->post(route('admin.polling.store'), planPost($event, [
            ['bidang_id' => $rkp->id, 'plot_size_index' => 0, 'size' => '3x6', 'plots_count' => 1],
        ]))
        ->assertSessionHasErrors(['bidang_allocations' => "Location 'Hall A' has no 3x6 plots. Add a 3x6 line to it, or give the allocation one of its sizes."]);

    expect(PlotSize::count())->toBe(0);
});

it('refuses the same location on two cards, and says to use size lines', function () {
    $event = Event::factory()->create();

    $this->actingAs(superAdmin(), 'admin')
        ->post(route('admin.polling.store'), planPost($event, [], [hall('B', ['3x3' => 2]), hall('B', ['3x6' => 2])]))
        ->assertSessionHasErrors(['plot_sizes' => "Location 'B' is on two cards. Keep one card for 'B' and add each booth size to it as a line."]);
});

it('refuses a craft category in a code allocation, because no registration can match it', function () {
    $event = Event::factory()->create();
    $category = KrafBidang::factory()->create();

    $this->actingAs(superAdmin(), 'admin')
        ->post(route('admin.polling.store'), planPost($event, [
            ['bidang_id' => $category->id, 'plot_size_index' => 0, 'size' => '3x3', 'plots_count' => 1],
        ]))
        ->assertSessionHasErrors('bidang_allocations');

    expect(Plot::count())->toBe(0);
});

it('refuses a size allocated past what the location holds of it, and leaves nothing half-written', function () {
    $event = Event::factory()->create();
    $rkp = KrafBidang::factory()->code('RKP')->create();

    // 8 plots in all against 8 allocated — but only 3 of them are 3x6.
    $this->actingAs(superAdmin(), 'admin')
        ->post(route('admin.polling.store'), planPost($event, [
            ['bidang_id' => $rkp->id, 'plot_size_index' => 0, 'size' => '3x3', 'plots_count' => 4],
            ['bidang_id' => $rkp->id, 'plot_size_index' => 0, 'size' => '3x6', 'plots_count' => 4],
        ], [hall('Hall A', ['3x3' => 5, '3x6' => 3])]))
        ->assertSessionHasErrors(['bidang_allocations' => "Location 'Hall A' holds 3 plots of 3x6, but 4 are allocated."]);

    expect(PlotSize::count())->toBe(0)
        ->and($event->fresh()->total_plots)->toBeNull();
});

it('rewrites the plan and records what the rewrite destroyed', function () {
    $event = Event::factory()->create();
    $admin = superAdmin();

    $this->actingAs($admin, 'admin')->post(route('admin.polling.store'), planPost($event));
    $this->actingAs($admin, 'admin')
        ->put(route('admin.polling.update', $event), planPost($event, [], [hall('Hall B', ['3x3' => 2])]))
        ->assertRedirect(route('admin.polling.show', $event));

    $rewrite = AuditTrail::where('action', 'plan_rewrite')->sole();

    expect($rewrite->new_values['cascade_deleted']['plots'])->toBe(5)
        ->and($rewrite->new_values['cascade_deleted']['plot_sizes'])->toBe(1)   // Hall A went
        ->and(Plot::where('event_id', $event->id)->count())->toBe(2)
        ->and(Plot::first()->plot_code)->toStartWith('HALL B-');
});

it('counts only the plan rows a rewrite really removed, not the pairs it kept', function () {
    $event = Event::factory()->create();
    $admin = superAdmin();
    $this->actingAs($admin, 'admin')->post(route('admin.polling.store'), planPost($event, [], [hall('Hall A', ['3x3' => 5])]));
    $kept = PlotSize::sole()->id;

    // Same location and size, a different count: the pair survives, by id.
    $this->actingAs($admin, 'admin')->put(route('admin.polling.update', $event), planPost($event, [], [hall('Hall A', ['3x3' => 3])]));

    $rewrite = AuditTrail::where('action', 'plan_rewrite')->sole();

    expect(PlotSize::sole()->id)->toBe($kept)
        ->and($rewrite->new_values['cascade_deleted']['plot_sizes'])->toBe(0)
        ->and($rewrite->old_values['plot_sizes'])->toBe([]);
});

it('locks the plan once a lot is held, preset included', function () {
    $event = Event::factory()->create();
    $admin = superAdmin();
    $this->actingAs($admin, 'admin')->post(route('admin.polling.store'), planPost($event));

    $reg = EventRegistration::factory()->create(['event_id' => $event->id]);
    $this->actingAs($admin, 'admin')->post(route('admin.polling.preset-assign', $event), [
        'plot_id' => Plot::first()->id,
        'registration_id' => $reg->id,
    ]);

    $this->actingAs($admin, 'admin')
        ->put(route('admin.polling.update', $event), planPost($event))
        ->assertSessionHasErrors('error');

    $this->actingAs($admin, 'admin')
        ->delete(route('admin.polling.destroy', $event))
        ->assertSessionHasErrors('error');

    expect(Plot::count())->toBe(5)->and($reg->fresh()->plot_id)->not->toBeNull();
});

it('clears a plan back to draft and says what went', function () {
    $event = Event::factory()->create();
    $admin = superAdmin();
    $this->actingAs($admin, 'admin')->post(route('admin.polling.store'), planPost($event));

    $this->actingAs($admin, 'admin')
        ->delete(route('admin.polling.destroy', $event))
        ->assertRedirect(route('admin.polling.index'));

    expect(Plot::count())->toBe(0)
        ->and(PlotSize::count())->toBe(0)
        ->and($event->fresh()->polling_start_date)->toBeNull()
        ->and(AuditTrail::where('action', 'plan_cleared')->value('new_values')['cascade_deleted']['plots'])->toBe(5);
});

/* ── The stages ─────────────────────────────────────────────────────────── */

it('will not open voting past the guard, and opens it once the guard is satisfied', function () {
    $event = Event::factory()->create(["polling_start_date" => now(), "polling_deadline" => now()->addDay()]);
    $admin = superAdmin();

    $this->actingAs($admin, 'admin')
        ->post(route('admin.polling.change-status', $event), ['polling_status' => 'polling'])
        ->assertSessionHasErrors('error');

    Plot::factory()->create(['event_id' => $event->id]);
    EventRegistration::factory()->coded('RKP')->create(['event_id' => $event->id]);

    $this->actingAs($admin, 'admin')
        ->post(route('admin.polling.change-status', $event), ['polling_status' => 'polling'])
        ->assertSessionHas('warning');   // RKP has no plot of its own; the open pool takes it

    expect($event->fresh()->polling_status)->toBe('polling');
});

it('refuses a transition the map does not allow, and completed while anyone is unseated', function () {
    $event = Event::factory()->create(['polling_status' => 'draft']);
    $admin = superAdmin();

    $this->actingAs($admin, 'admin')
        ->post(route('admin.polling.change-status', $event), ['polling_status' => 'completed'])
        ->assertSessionHasErrors('error');

    $event->update(['polling_status' => 'closed']);
    EventRegistration::factory()->create(['event_id' => $event->id]);

    $this->actingAs($admin, 'admin')
        ->post(route('admin.polling.change-status', $event), ['polling_status' => 'completed'])
        ->assertSessionHasErrors('error');

    expect($event->fresh()->polling_status)->toBe('closed');
});

it('assigns the remaining lots only once voting is closed', function () {
    $event = Event::factory()->create(['polling_status' => 'polling']);
    Plot::factory()->count(2)->create(['event_id' => $event->id]);
    EventRegistration::factory()->count(2)->coded('RKP')->create(['event_id' => $event->id]);
    $admin = superAdmin();

    $this->actingAs($admin, 'admin')
        ->post(route('admin.polling.process', $event))
        ->assertSessionHasErrors('error');

    expect(Poll::count())->toBe(0);

    $event->update(['polling_status' => 'closed']);

    $this->actingAs($admin, 'admin')
        ->post(route('admin.polling.process', $event))
        ->assertSessionHas('success');

    expect(Poll::count())->toBe(2)
        ->and($event->fresh()->polling_status)->toBe('completed');
});

it('takes back a drawn lot with its Poll, so the company can draw again', function () {
    $event = Event::factory()->create(['polling_status' => 'closed']);
    Plot::factory()->create(['event_id' => $event->id]);
    $reg = EventRegistration::factory()->coded('RKP')->create(['event_id' => $event->id]);
    $admin = superAdmin();

    $this->actingAs($admin, 'admin')->post(route('admin.polling.process', $event));
    $plot = $reg->fresh()->plot;

    $this->actingAs($admin, 'admin')
        ->post(route('admin.polling.unassign-plot', [$event, $plot]))
        ->assertSessionHas('success', fn ($m) => str_contains($m, 'was drawn'));

    expect(Poll::count())->toBe(0)
        ->and($reg->fresh()->plot_id)->toBeNull()
        ->and($plot->fresh()->is_allocated)->toBeFalse();
});

it('refuses a plot or a size from another event through this event URL', function () {
    $event = Event::factory()->create();
    $other = Plot::factory()->create(['is_allocated' => true]);
    $admin = superAdmin();

    $this->actingAs($admin, 'admin')
        ->post(route('admin.polling.unassign-plot', [$event, $other]))
        ->assertNotFound();

    $this->actingAs($admin, 'admin')
        ->get(route('admin.polling.plots.by-size', [$event, $other->plotSize]))
        ->assertNotFound();

    expect($other->fresh()->is_allocated)->toBeTrue();
});

/* ── Who sees what ──────────────────────────────────────────────────────── */

it('lets a state admin read an open draw, with only their own zone in it, and write nothing', function () {
    $event = Event::factory()->create(['status' => 'open', 'title' => 'Craft Fair']);
    Plot::factory()->create(['event_id' => $event->id]);

    $mine = Company::factory()->create(['state' => 'Johor', 'name' => 'Johor Weaver']);
    $theirs = Company::factory()->create(['state' => 'Kedah', 'name' => 'Kedah Carver']);
    EventRegistration::factory()->create(['event_id' => $event->id, 'company_id' => $mine->id]);
    EventRegistration::factory()->create(['event_id' => $event->id, 'company_id' => $theirs->id]);

    $admin = stateAdmin('Johor');

    $this->actingAs($admin, 'admin')
        ->get(route('admin.polling.show', $event))
        ->assertOk()
        ->assertSee('Johor Weaver')
        ->assertDontSee('Kedah Carver')
        ->assertDontSee(route('admin.polling.change-status', $event));

    $this->actingAs($admin, 'admin')
        ->post(route('admin.polling.change-status', $event), ['polling_status' => 'polling'])
        ->assertForbidden();

    $this->actingAs($admin, 'admin')->get(route('admin.polling.create'))->assertForbidden();
});

it('renders every page of the lot draw', function () {
    $event = Event::factory()->create(['status' => 'open']);
    $rkp = KrafBidang::factory()->code('RKP')->create();
    $admin = superAdmin();

    $this->actingAs($admin, 'admin')->get(route('admin.polling.create'))->assertOk()->assertSee($event->title);

    $this->actingAs($admin, 'admin')
        ->post(route('admin.polling.store-settings'), [
            'event_id' => $event->id,
            'polling_start_date' => '2026-11-01T09:00',
            'polling_deadline' => '2026-11-05T17:00',
        ])
        ->assertRedirect(route('admin.polling.create-plan'));

    EventRegistration::factory()->coded('RKP')->create(['event_id' => $event->id, 'plot_size' => '3x6']);

    $this->actingAs($admin, 'admin')->get(route('admin.polling.create-plan'))
        ->assertOk()
        ->assertSee('RKP 3x6');

    // The settings came from the session; the form posts only the plan.
    $this->actingAs($admin, 'admin')
        ->post(route('admin.polling.store'), [
            'plot_sizes' => [hall('A', ['3x3' => 1, '3x6' => 1])],
            'bidang_allocations' => [['bidang_id' => $rkp->id, 'plot_size_index' => 0, 'size' => '3x6', 'plots_count' => 1]],
        ])
        ->assertRedirect(route('admin.polling.show', $event));

    $this->actingAs($admin, 'admin')->get(route('admin.polling.index'))->assertOk()->assertSee($event->title);
    $this->actingAs($admin, 'admin')->get(route('admin.polling.show', $event))->assertOk()->assertSee('A-RKP1');
    $this->actingAs($admin, 'admin')->get(route('admin.polling.edit', $event))->assertOk()->assertSee('RKP 3x6');
    $this->actingAs($admin, 'admin')
        ->get(route('admin.polling.plots.by-size', [$event, $event->plotSizes()->first()]))
        ->assertOk();
});
