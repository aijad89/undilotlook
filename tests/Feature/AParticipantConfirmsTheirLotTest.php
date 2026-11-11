<?php

use App\Models\Company;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\KrafBidang;
use App\Models\Plot;
use App\Models\PlotSize;
use App\Models\Poll;

/**
 * The participant's side: Confirm draws the lot, the page reveals it, and the
 * dashboard says where each draw stands. Ends with one whole draw, from an
 * empty event to every company seated, through the screens alone.
 */
function openDraw(array $attributes = []): Event
{
    return Event::factory()->create($attributes + [
        'status' => 'open',
        'polling_status' => 'polling',
        'polling_start_date' => now()->subDay(),
        'polling_deadline' => now()->addDay(),
    ]);
}

function inDraw(Event $event, string $code = 'RKP', array $extra = []): EventRegistration
{
    return EventRegistration::factory()->create($extra + [
        'event_id' => $event->id,
        'company_id' => Company::factory(),
        'code' => $code,
    ]);
}

it('draws a lot of the company own code on Confirm, and reveals it on the next page', function () {
    $event = openDraw();
    $rkp = KrafBidang::factory()->code('RKP')->create();
    $size = PlotSize::factory()->create(['event_id' => $event->id]);
    $theirs = Plot::factory()->on($size)->coded($rkp)->create(['plot_code' => 'A-RKP1']);
    Plot::factory()->on($size)->create();

    $entry = inDraw($event);
    $user = $entry->company->user;

    $this->actingAs($user)
        ->post(route('zvote.submit', $event), ['agreement' => '1'])
        ->assertRedirect(route('zvote.index', $event))
        ->assertSessionHas('just_drawn');

    expect($entry->fresh()->plot_id)->toBe($theirs->id)
        ->and(Poll::where('user_id', $user->id)->count())->toBe(1);

    $this->actingAs($user)
        ->withSession(['just_drawn' => true])
        ->get(route('zvote.index', $event))
        ->assertOk()
        ->assertSee('A-RKP1')
        ->assertSee('data-lot-reveal', false);
});

it('confirms once: a second Confirm draws nothing more', function () {
    $event = openDraw();
    Plot::factory()->count(2)->create(['event_id' => $event->id]);
    $user = inDraw($event)->company->user;

    $this->actingAs($user)->post(route('zvote.submit', $event), ['agreement' => '1']);
    $this->actingAs($user)
        ->post(route('zvote.submit', $event), ['agreement' => '1'])
        ->assertSessionHas('error', 'You have already confirmed your lot.');

    expect(Plot::where('is_allocated', true)->count())->toBe(1)
        ->and(Poll::count())->toBe(1);
});

it('keeps a preset a surprise until Confirm, then hands over that lot', function () {
    $event = openDraw();
    $reserved = Plot::factory()->create(['event_id' => $event->id, 'plot_code' => 'VIP-1']);
    Plot::factory()->create(['event_id' => $event->id, 'plot_code' => 'OPEN-1']);

    $entry = inDraw($event, 'RKP', ['plot_id' => $reserved->id]);
    $reserved->update([
        'company_id' => $entry->company_id,
        'allocated_to' => $entry->user_id,
        'is_allocated' => true,
        'allocation_type' => 'preset',
        'is_locked' => true,
    ]);
    $user = $entry->company->user;

    $this->actingAs($user)->get(route('zvote.index', $event))
        ->assertOk()
        ->assertDontSee('VIP-1');

    $this->actingAs($user)->post(route('zvote.submit', $event), ['agreement' => '1']);

    expect($entry->fresh()->plot_id)->toBe($reserved->id)
        ->and(Plot::where('plot_code', 'OPEN-1')->value('is_allocated'))->toBeFalse();
});

it('prints the slip for a lot that is held, and for nothing else', function () {
    $event = openDraw();
    Plot::factory()->create(['event_id' => $event->id, 'plot_code' => 'A-RKP1']);
    $user = inDraw($event)->company->user;

    $this->actingAs($user)->get(route('zvote.slip', $event))->assertNotFound();

    $this->actingAs($user)->post(route('zvote.submit', $event), ['agreement' => '1']);

    $this->actingAs($user)->get(route('zvote.index', $event))->assertSee(route('zvote.slip', $event));

    $slip = $this->actingAs($user)->get(route('zvote.slip', $event))->assertOk();
    expect($slip->headers->get('content-type'))->toBe('application/pdf')
        ->and(substr($slip->getContent(), 0, 5))->toBe('%PDF-');

    $this->actingAs(Company::factory()->create()->user)->get(route('zvote.slip', $event))->assertNotFound();
});

it('shows nothing of an event the company is not in the draw for', function () {
    $event = openDraw();
    $outsider = Company::factory()->create()->user;

    $this->actingAs($outsider)->get(route('zvote.index', $event))->assertNotFound();
    $this->actingAs($outsider)->post(route('zvote.submit', $event), ['agreement' => '1'])->assertNotFound();
});

it('refuses Confirm when the draw is not open, past its deadline, or the code is unset', function () {
    $closed = openDraw(['polling_status' => 'closed']);
    $late = openDraw(['polling_deadline' => now()->subMinute()]);
    $uncoded = openDraw();

    // A free lot in each, so the refusal is the rule under test and never
    // "nothing left to draw".
    foreach ([[$closed, 'RKP', 'not open'], [$late, 'RKP', 'not open'], [$uncoded, null, 'craft code']] as [$event, $code, $says]) {
        Plot::factory()->create(['event_id' => $event->id]);
        $user = inDraw($event, 'RKP', ['code' => $code])->company->user;

        $this->actingAs($user)
            ->post(route('zvote.submit', $event), ['agreement' => '1'])
            ->assertSessionHas('error', fn ($message) => str_contains($message, $says));
    }

    $this->actingAs(inDraw($closed)->company->user)->get(route('zvote.index', $closed))->assertForbidden();

    expect(Poll::count())->toBe(0);
});

it('lists every draw on the dashboard with what is left to do in each', function () {
    $open = openDraw(['title' => 'Open Fair', 'start_date' => now()->addMonth(), 'end_date' => now()->addMonth()->addDays(2)]);
    $soon = openDraw(['title' => 'Coming Fair', 'polling_status' => 'draft', 'polling_start_date' => now()->addWeek(), 'start_date' => now()->addMonths(2), 'end_date' => now()->addMonths(2)->addDays(2)]);
    $planning = openDraw(['title' => 'Planning Fair', 'polling_status' => 'draft', 'polling_start_date' => null, 'start_date' => now()->addMonths(3), 'end_date' => now()->addMonths(3)->addDays(2)]);
    $cancelled = openDraw(['title' => 'Called Off Fair', 'polling_status' => 'cancelled']);

    $company = Company::factory()->create();
    foreach ([$open, $soon, $planning, $cancelled] as $event) {
        inDraw($event, 'RKP', ['company_id' => $company->id, 'user_id' => $company->user_id]);
    }

    // On the list but undated is still "you are in this draw", never an empty page.
    $this->actingAs($company->user)->get(route('dashboard'))
        ->assertOk()
        // Latest event first.
        ->assertSeeInOrder(['Planning Fair', 'Coming Fair', 'Open Fair'])
        ->assertSee(route('zvote.index', $open))
        ->assertSee('The draw date has not been set yet')
        ->assertDontSee('Called Off Fair');
});

/* ── One whole draw ─────────────────────────────────────────────────────── */

it('runs a whole draw through the screens, from an empty event to everyone seated', function () {
    $admin = superAdmin();
    $event = Event::factory()->create(['status' => 'open']);
    $rkp = KrafBidang::factory()->code('RKP')->create();
    $tbps = KrafBidang::factory()->code('TBPS')->create();

    // The office puts three companies into the draw.
    $companies = Company::factory()->count(3)->create(['state' => 'Johor']);
    $this->actingAs($admin, 'admin')->post(route('admin.events.registrations.store', $event), [
        'company_id' => $companies->take(2)->pluck('id')->all(), 'code' => 'RKP', 'plot_size' => '3x3',
    ]);
    $this->actingAs($admin, 'admin')->post(route('admin.events.registrations.store', $event), [
        'company_id' => [$companies[2]->id], 'code' => 'TBPS', 'plot_size' => '3x3',
    ]);

    // A plan: two RKP lots, one TBPS lot, one spare in the open pool.
    $this->actingAs($admin, 'admin')->post(route('admin.polling.store'), [
        'event_id' => $event->id,
        'polling_start_date' => now()->subHour()->format('Y-m-d\TH:i'),
        'polling_deadline' => now()->addDay()->format('Y-m-d\TH:i'),
        'plot_sizes' => [['location' => 'Dewan', 'sizes' => [['size' => '3x3', 'quantity' => 4]]]],
        'bidang_allocations' => [
            ['bidang_id' => $rkp->id, 'plot_size_index' => 0, 'size' => '3x3', 'plots_count' => 2],
            ['bidang_id' => $tbps->id, 'plot_size_index' => 0, 'size' => '3x3', 'plots_count' => 1],
        ],
    ])->assertRedirect(route('admin.polling.show', $event));

    $this->actingAs($admin, 'admin')
        ->post(route('admin.polling.change-status', $event), ['polling_status' => 'polling'])
        ->assertSessionHas('success');

    // One participant confirms for themselves.
    $this->actingAs($companies[2]->user)
        ->post(route('zvote.submit', $event), ['agreement' => '1'])
        ->assertSessionHas('just_drawn');

    // The office closes voting and assigns everyone else.
    $this->actingAs($admin, 'admin')->post(route('admin.polling.change-status', $event), ['polling_status' => 'closed']);
    $this->actingAs($admin, 'admin')
        ->post(route('admin.polling.process', $event))
        ->assertSessionHas('success');

    $seats = EventRegistration::with('plot.bidang')->get()->mapWithKeys(fn ($r) => [$r->company_id => $r->plot?->bidang?->code]);

    expect($event->fresh()->polling_status)->toBe('completed')
        ->and(Poll::count())->toBe(3)
        ->and($seats[$companies[0]->id])->toBe('RKP')
        ->and($seats[$companies[1]->id])->toBe('RKP')
        ->and($seats[$companies[2]->id])->toBe('TBPS')
        ->and(Plot::where('is_allocated', false)->whereNull('bidang_id')->count())->toBe(1);

    // And each participant sees their own lot.
    $lot = EventRegistration::where('company_id', $companies[0]->id)->sole()->plot->plot_code;
    $this->actingAs($companies[0]->user)->get(route('dashboard'))->assertSee($lot);
});

it('shows the lot page the same stages as the dashboard, Open to Completed', function () {
    $event = openDraw();
    $user = inDraw($event)->company->user;

    foreach ([route('dashboard'), route('zvote.index', $event)] as $url) {
        $this->actingAs($user, 'web')->get($url)
            ->assertOk()
            ->assertSeeInOrder(['class="label">Open<', 'class="label">Closed<', 'class="label">Completed<'], false)
            ->assertDontSee('class="label">Draft<', false)
            ->assertDontSee('class="label">End<', false);
    }
});
