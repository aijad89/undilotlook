<?php

use App\Models\Company;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\KrafBidang;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Putting a company on an event's draw list IS the approval — there is no status
 * to set afterwards. What the office still has to say is the craft code it draws
 * under and the booth size it needs, because the plan is built from those two.
 */
it('adds companies to the draw, with the account the draw keys on', function () {
    $event = Event::factory()->create();
    $a = Company::factory()->create(['state' => 'Johor']);
    $b = Company::factory()->create(['state' => 'Johor']);

    $this->actingAs(superAdmin(), 'admin')
        ->post(route('admin.events.registrations.store', $event), [
            'company_id' => [$a->id, $b->id],
            'code' => 'TBPS',
            'plot_size' => '3x6',
        ])
        ->assertRedirect();

    $registration = EventRegistration::where('company_id', $a->id)->sole();

    expect(EventRegistration::count())->toBe(2)
        ->and($registration->code)->toBe('TBPS')
        ->and($registration->plot_size)->toBe('3x6')
        // Ownership is company_id; user_id is the ACCOUNT that draws, copied from
        // the company at this point. Two keys, two questions.
        ->and($registration->user_id)->toBe($a->user_id);
});

it('refuses a company from another zone even when its id is posted directly', function () {
    $event = Event::factory()->create();
    $theirs = Company::factory()->create(['state' => 'Kedah']);

    $this->actingAs(stateAdmin('Johor'), 'admin')
        ->post(route('admin.events.registrations.store', $event), ['company_id' => [$theirs->id]])
        ->assertRedirect();

    expect(EventRegistration::count())->toBe(0);
});

it('adds a company once, however many times the form is submitted', function () {
    $event = Event::factory()->create();
    $company = Company::factory()->create(['state' => 'Johor']);
    $admin = stateAdmin('Johor');

    foreach (range(1, 2) as $ignored) {
        $this->actingAs($admin, 'admin')
            ->post(route('admin.events.registrations.store', $event), ['company_id' => [$company->id]]);
    }

    expect(EventRegistration::count())->toBe(1);
});

it('stamps code_updated_at only when the code actually changes', function () {
    $registration = EventRegistration::factory()->coded('TBPS')->create();
    $registration->update(['code_updated_at' => null]);

    $save = fn (string $code) => $this->actingAs(superAdmin(), 'admin')
        ->put(route('admin.events.registrations.update', [$registration->event_id, $registration]), [
            'code' => $code,
            'plot_size' => '3x3',
        ]);

    $save('TBPS');
    expect($registration->fresh()->code_updated_at)->toBeNull();

    $save('RKP');
    expect($registration->fresh()->code)->toBe('RKP')
        ->and($registration->fresh()->code_updated_at)->not->toBeNull();
});

/**
 * A drawn lot is the thing this application exists to produce. It may not
 * disappear because somebody tidied a list.
 */
it('refuses to remove a row that holds a lot, one at a time and in bulk', function () {
    $event = Event::factory()->create();
    $holder = EventRegistration::factory()->create(['event_id' => $event->id]);
    $other = EventRegistration::factory()->create(['event_id' => $event->id]);

    $plotId = DB::table('plots')->insertGetId([
        'event_id' => $event->id,
        'plot_size_id' => DB::table('plot_sizes')->insertGetId([
            'event_id' => $event->id, 'size' => '3x3', 'quantity' => 1, 'location' => null,
            'created_at' => now(), 'updated_at' => now(),
        ]),
        'company_id' => $holder->company_id,
        'allocated_to' => null, 'bidang_id' => null, 'sub_bidang_id' => null,
        'plot_code' => 'A1', 'is_allocated' => true, 'allocation_type' => 'algorithm',
        'is_locked' => false, 'allocated_at' => now(),
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $holder->update(['plot_id' => $plotId]);

    $admin = superAdmin();

    $this->actingAs($admin, 'admin')
        ->delete(route('admin.events.registrations.destroy', [$event, $holder]))
        ->assertRedirect();

    $this->actingAs($admin, 'admin')
        ->post(route('admin.events.registrations.bulk', $event), [
            'action' => 'remove', 'removal_reason' => 'Payment refunded',
            'registration_id' => [$holder->id, $other->id],
        ])
        ->assertRedirect();

    expect($holder->fresh())->not->toBeNull()
        ->and($other->fresh())->toBeNull();
});

it('puts back exactly what the last removal took', function () {
    $event = Event::factory()->create();
    $company = Company::factory()->create(['state' => 'Johor']);

    // Not yet confirmed to the company, so the zone may still take it out.
    EventRegistration::factory()->coded('TBPS')->unverified()->create([
        'event_id' => $event->id,
        'company_id' => $company->id,
        'user_id' => $company->user_id,
        'plot_size' => '3x9',
    ]);

    $admin = stateAdmin('Johor');

    $this->actingAs($admin, 'admin')
        ->post(route('admin.events.registrations.bulk', $event), [
            'action' => 'remove',
            'registration_id' => EventRegistration::pluck('id')->all(),
        ]);

    expect(EventRegistration::count())->toBe(0);

    $this->actingAs($admin, 'admin')
        ->post(route('admin.events.registrations.undo', $event))
        ->assertRedirect();

    $restored = EventRegistration::sole();

    expect($restored->company_id)->toBe($company->id)
        ->and($restored->code)->toBe('TBPS')
        ->and($restored->plot_size)->toBe('3x9');
});

it('sets a code across every ticked row', function () {
    $event = Event::factory()->create();
    EventRegistration::factory()->count(3)->create(['event_id' => $event->id]);

    $this->actingAs(superAdmin(), 'admin')
        ->post(route('admin.events.registrations.bulk', $event), [
            'action' => 'set_code',
            'registration_id' => EventRegistration::pluck('id')->all(),
            'code' => 'RKP',
        ]);

    expect(EventRegistration::where('code', 'RKP')->count())->toBe(3);
});

/* ── The company form writes two rows ─────────────────────────────────────── */

it('creates the company and its one account from a single form', function () {
    $this->actingAs(stateAdmin('Johor'), 'admin')
        ->post(route('admin.companies.store'), [
            'name' => 'Kraf Tenun',
            'state' => 'Johor',
            'owner_name' => 'Aminah',
            'email' => 'aminah@example.test',
            'phone' => '012-3456789',
            'password' => 'kraf1234',
            'password_confirmation' => 'kraf1234',
        ])
        ->assertRedirect();

    $company = Company::sole();
    $user = User::sole();

    expect($company->user_id)->toBe($user->id)
        ->and($user->email)->toBe('aminah@example.test')
        ->and($user->is_active)->toBeTrue()
        ->and(Hash::check('kraf1234', $user->password))->toBeTrue();
});

it('stores an IC typed as twelve bare digits in its dashed form, so one IC is one spelling', function () {
    $existing = Company::factory()->create(['state' => 'Johor']);
    $existing->user->update(['ic_number' => '880101-01-5555']);

    $post = fn (string $ic, string $email) => $this->actingAs(stateAdmin('Johor'), 'admin')
        ->post(route('admin.companies.store'), [
            'name' => 'Kraf '.$email, 'state' => 'Johor', 'owner_name' => 'Owner',
            'email' => $email, 'ic_number' => $ic,
        ]);

    // The same IC as the existing account, typed without dashes: refused as a
    // duplicate, never stored as a second spelling.
    $post('880101015555', 'twin@example.test')->assertSessionHasErrors('ic_number');

    $post('900202 02 6666', 'new@example.test')->assertRedirect();
    expect(User::where('email', 'new@example.test')->value('ic_number'))->toBe('900202-02-6666');

    $post('12345', 'short@example.test')
        ->assertSessionHasErrors(['ic_number' => 'An IC number has twelve digits, written 990101-01-1234.']);
});

it('refuses a second company on one email, with a message rather than a database error', function () {
    $existing = Company::factory()->create(['state' => 'Johor']);

    $this->actingAs(stateAdmin('Johor'), 'admin')
        ->post(route('admin.companies.store'), [
            'name' => 'Second Company',
            'state' => 'Johor',
            'owner_name' => 'Someone',
            'email' => $existing->user->email,
        ])
        ->assertSessionHasErrors('email');

    expect(Company::count())->toBe(1);
});

it('keeps a craft selection on the hierarchy half, and refuses a mismatched pair', function () {
    $company = Company::factory()->create(['state' => 'Johor']);
    $category = KrafBidang::factory()->create(['name' => 'TEKSTIL']);
    $otherCategory = KrafBidang::factory()->create(['name' => 'SERAMIK']);

    $sub = App\Models\KrafSubBidang::factory()->create(['bidang_id' => $category->id]);
    $strayCategorySub = App\Models\KrafSubBidang::factory()->create(['bidang_id' => $otherCategory->id]);

    $admin = stateAdmin('Johor');

    $this->actingAs($admin, 'admin')
        ->post(route('admin.companies.kraf.store', $company), [
            'bidang_id' => $category->id,
            'sub_bidang_id' => [$strayCategorySub->id],
        ]);

    expect($company->krafSelections()->count())->toBe(0);

    $this->actingAs($admin, 'admin')
        ->post(route('admin.companies.kraf.store', $company), [
            'bidang_id' => $category->id,
            'sub_bidang_id' => [$sub->id],
        ]);

    $selection = $company->krafSelections()->sole();

    expect($selection->sub_bidang_id)->toBe($sub->id)
        // The first selection is the primary, with nothing to say about it.
        ->and($selection->is_primary)->toBeTrue();
});

it('refuses a coding-sheet row as a company craft category', function () {
    $company = Company::factory()->create(['state' => 'Johor']);
    $code = KrafBidang::factory()->code('TBPS')->create();

    $this->actingAs(stateAdmin('Johor'), 'admin')
        ->post(route('admin.companies.kraf.store', $company), [
            'bidang_id' => $code->id,
            'sub_bidang_id' => [1],
        ])
        ->assertSessionHasErrors('bidang_id');
});

it('refuses to delete a company that holds a lot, in words an admin can read', function () {
    $registration = EventRegistration::factory()->create();
    $company = $registration->company;
    $company->update(['state' => 'Johor']);

    $plotId = DB::table('plots')->insertGetId([
        'event_id' => $registration->event_id,
        'plot_size_id' => DB::table('plot_sizes')->insertGetId([
            'event_id' => $registration->event_id, 'size' => '3x3', 'quantity' => 1, 'location' => null,
            'created_at' => now(), 'updated_at' => now(),
        ]),
        'company_id' => $company->id,
        'allocated_to' => null, 'bidang_id' => null, 'sub_bidang_id' => null,
        'plot_code' => 'B2', 'is_allocated' => true, 'allocation_type' => 'algorithm',
        'is_locked' => false, 'allocated_at' => now(),
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $registration->update(['plot_id' => $plotId]);

    $this->actingAs(stateAdmin('Johor'), 'admin')
        ->delete(route('admin.companies.destroy', $company))
        ->assertRedirect()
        ->assertSessionHas('error');

    expect($company->fresh())->not->toBeNull();
});

it('shows each row the lot number it holds', function () {
    $event = Event::factory()->create();
    $plot = \App\Models\Plot::factory()->create(['event_id' => $event->id, 'plot_code' => 'A-TBP1']);
    EventRegistration::factory()->create(['event_id' => $event->id, 'plot_id' => $plot->id]);

    $this->actingAs(superAdmin(), 'admin')->get(route('admin.events.registrations.index', $event))
        ->assertOk()->assertSee('A-TBP1');
});

/*
 * The zone checks the payment and approves; only HQ sets the craft code and
 * booth size the plan is built from. The zone reads them.
 */
it('lets a zone admin check everything and approve; HQ verifies, or returns it with a note', function () {
    Illuminate\Support\Facades\Storage::fake('local');
    KrafBidang::factory()->code('RKP')->create();
    $event = Event::factory()->create();
    $company = Company::factory()->create(['state' => 'Johor', 'kraf_code' => 'TBPS', 'plot_size' => '3x6']);
    Illuminate\Support\Facades\Storage::disk('local')->put("join-requests/{$event->id}/r.jpg", 'jpeg');
    $request = App\Models\EventJoinRequest::factory()->create(['event_id' => $event->id, 'company_id' => $company->id, 'payment_method' => 'transfer', 'documents' => [
        ['name' => 'Payment receipt', 'path' => "join-requests/{$event->id}/r.jpg", 'original' => 'resit.jpg'],
        ['name' => 'Product photo 1', 'path' => "products/{$company->id}/p.jpg", 'original' => 'p.jpg'],
    ]]);
    $zone = stateAdmin('Johor');
    $hq = superAdmin();
    $index = route('admin.events.registrations.index', $event);

    // The zone sees the proof and picks the code and size itself.
    $this->actingAs($zone, 'admin')->get($index)->assertOk()
        ->assertSeeInOrder(['Online transfer', 'Payment receipt', 'class="mac-proof-pop"', 'Product photo 1'], false)
        ->assertSee('aria-label="Craft code"', false);
    $this->post(route('admin.events.registrations.approve', [$event, $request]), ['code' => 'RKP', 'plot_size' => '3x9'])
        ->assertSessionHas('success');
    $row = EventRegistration::sole();
    expect($row->code)->toBe('RKP')->and($row->plot_size)->toBe('3x9')->and($row->verified_at)->toBeNull();

    // Only HQ verifies or returns.
    $this->post(route('admin.events.registrations.bulk', $event), ['action' => 'verify', 'registration_id' => [$row->id]])->assertForbidden();
    $this->post(route('admin.events.registrations.return', [$event, $row]), ['hq_note' => 'x'])->assertForbidden();
    expect($row->fresh()->verified_at)->toBeNull();

    // HQ returns it with a note; the zone sees it, fixes it, and it waits again.
    $this->actingAs($hq, 'admin')->get($index)->assertSee('Payment receipt')->assertSee('Waiting for HQ');
    $this->post(route('admin.events.registrations.return', [$event, $row]), ['hq_note' => 'Booth should be 3x6.'])->assertSessionHas('success');
    $this->get($index)->assertDontSee('1 waiting for HQ');
    $this->actingAs($zone, 'admin')->get($index)->assertSee('Returned by HQ')->assertSee('Booth should be 3x6.');
    $this->put(route('admin.events.registrations.update', [$event, $row]), ['code' => 'RKP', 'plot_size' => '3x6']);
    expect($row->fresh()->hq_note)->toBeNull()->and($row->fresh()->verified_at)->toBeNull();

    // HQ verifies; a zone change afterwards, from any door, sends it back.
    $this->actingAs($hq, 'admin')->post(route('admin.events.registrations.bulk', $event), ['action' => 'verify', 'registration_id' => [$row->id]]);
    expect($row->fresh()->verified_at)->not->toBeNull()->and($row->fresh()->verified_by)->toBe($hq->id);
    $this->actingAs($zone, 'admin')->post(route('admin.events.registrations.bulk', $event), ['action' => 'set_code', 'registration_id' => [$row->id], 'code' => 'TBPS']);
    expect($row->fresh()->verified_at)->toBeNull();
    $this->actingAs($hq, 'admin')->post(route('admin.events.registrations.bulk', $event), ['action' => 'verify', 'registration_id' => [$row->id]]);
    $this->actingAs($zone, 'admin')->put(route('admin.companies.kraf-code.update', $company), ['kraf_code' => 'RKP', 'plot_size' => '3x9'])->assertSessionHas('success');
    expect($row->fresh()->plot_size)->toBe('3x9')->and($row->fresh()->verified_at)->toBeNull();

    // HQ verified it once, so the company was told: only HQ takes it off the list.
    $this->actingAs($hq, 'admin')->post(route('admin.events.registrations.bulk', $event), ['action' => 'remove', 'registration_id' => [$row->id], 'removal_reason' => 'Payment refunded'])->assertSessionHas('success');
    expect(EventRegistration::where('company_id', $company->id)->exists())->toBeFalse();
});

it('shows a row the office added as having no payment proof, and lets HQ edit its code and size', function () {
    $event = Event::factory()->create();
    $company = Company::factory()->create(['state' => 'Johor']);
    $row = EventRegistration::factory()->create(['event_id' => $event->id, 'company_id' => $company->id]);

    $this->actingAs(superAdmin(), 'admin')->get(route('admin.events.registrations.index', $event))
        ->assertOk()
        ->assertSee('Added by the office')
        ->assertSee('value="PUT"', false)
        ->assertSee('value="set_code"', false);
});
it('clears HQ note when the zone fixes the row in bulk', function () {
    $company = Company::factory()->create(['state' => 'Johor']);
    $row = EventRegistration::factory()->unverified()->create(['company_id' => $company->id, 'hq_note' => 'Wrong code.']);

    $this->actingAs(stateAdmin('Johor'), 'admin')->post(route('admin.events.registrations.bulk', $row->event_id), [
        'action' => 'set_code', 'registration_id' => [$row->id], 'code' => 'RKP',
    ])->assertSessionHas('success');

    expect($row->fresh()->hq_note)->toBeNull()->and($row->fresh()->code)->toBe('RKP');
});

it('keeps the code and size as drawn once the draw has started, for HQ too', function () {
    $event = Event::factory()->create(['polling_status' => 'polling']);
    $company = Company::factory()->create(['state' => 'Johor']);
    $row = EventRegistration::factory()->coded('TBPS')->create(['event_id' => $event->id, 'company_id' => $company->id]);

    foreach ([stateAdmin('Johor'), superAdmin()] as $admin) {
        $this->actingAs($admin, 'admin')->get(route('admin.events.registrations.index', $event))
            ->assertOk()->assertDontSee('value="set_code"', false);
        $this->put(route('admin.events.registrations.update', [$event, $row]), ['code' => 'RKP', 'plot_size' => '3x9'])
            ->assertSessionHas('error');
        $this->post(route('admin.events.registrations.bulk', $event), ['action' => 'set_size', 'registration_id' => [$row->id], 'plot_size' => '3x9'])
            ->assertSessionHas('error');
    }

    // Still verified, so the participant still sees it.
    expect($row->fresh()->code)->toBe('TBPS')->and($row->fresh()->plot_size)->toBe('3x3')
        ->and($row->fresh()->verified_at)->not->toBeNull();
});

it('lets the zone write how an office-added company paid, which HQ checks again', function () {
    $company = Company::factory()->create(['state' => 'Johor']);
    $row = EventRegistration::factory()->create(['company_id' => $company->id, 'hq_note' => null]);
    $index = route('admin.events.registrations.index', $row->event_id);
    $note = route('admin.events.registrations.payment-note', [$row->event_id, $row]);

    $this->actingAs(stateAdmin('Kedah'), 'admin')->put($note, ['payment_note' => 'x'])->assertForbidden();

    $this->actingAs(stateAdmin('Johor'), 'admin')->get($index)->assertSee('no payment note yet');
    $this->put($note, ['payment_note' => 'Receipt 00123, cash, 3 Nov'])->assertSessionHas('success');
    expect($row->fresh()->payment_note)->toBe('Receipt 00123, cash, 3 Nov')
        ->and($row->fresh()->verified_at)->toBeNull();

    $this->actingAs(superAdmin(), 'admin')->get($index)->assertSee('Receipt 00123, cash, 3 Nov');
});

it('lets a zone admin remove only a row the company has not been told of; HQ removes the rest', function () {
    $event = Event::factory()->create();
    $told = EventRegistration::factory()->create(['event_id' => $event->id, 'company_id' => Company::factory()->create(['state' => 'Johor'])->id]);
    $waiting = EventRegistration::factory()->unverified()->create(['event_id' => $event->id, 'company_id' => Company::factory()->create(['state' => 'Johor'])->id]);
    $zone = stateAdmin('Johor');

    $this->actingAs($zone, 'admin')->delete(route('admin.events.registrations.destroy', [$event, $told]))
        ->assertSessionHas('error', 'Only HQ can remove that company from the list now.');
    $this->post(route('admin.events.registrations.bulk', $event), ['action' => 'remove', 'registration_id' => [$told->id, $waiting->id]])
        ->assertSessionHas('success', '1 removed from the draw list. 1 kept: only HQ can remove them now.');
    expect($told->fresh())->not->toBeNull()->and($waiting->fresh())->toBeNull();

    $this->actingAs(superAdmin(), 'admin')->delete(route('admin.events.registrations.destroy', [$event, $told]), ['removal_reason' => 'Payment refunded'])
        ->assertSessionHas('success');
    expect($told->fresh())->toBeNull();
});

it('lets only HQ add a company once the draw has started, verified at once', function () {
    $event = Event::factory()->create(['polling_status' => 'polling']);
    [$a, $b, $c] = Company::factory()->count(3)->create(['state' => 'Johor']);
    $request = App\Models\EventJoinRequest::factory()->create(['event_id' => $event->id, 'company_id' => $c->id]);
    $zone = stateAdmin('Johor');

    $this->actingAs($zone, 'admin')->get(route('admin.events.registrations.index', $event))
        ->assertSee('The draw has started: only HQ can add companies now.')
        ->assertSee('The draw has started: only HQ can approve now.');
    $this->post(route('admin.events.registrations.store', $event), ['company_id' => [$a->id]])
        ->assertSessionHas('error', 'The draw has started: only HQ can add companies now.');
    $this->post(route('admin.events.registrations.approve', [$event, $request]), ['plot_size' => '3x3'])
        ->assertSessionHas('error');
    expect(EventRegistration::count())->toBe(0)->and($request->fresh()->status)->toBe('pending');

    $this->actingAs(superAdmin(), 'admin')->post(route('admin.events.registrations.store', $event), ['company_id' => [$b->id]])
        ->assertSessionHas('success');
    expect(EventRegistration::sole()->verified_at)->not->toBeNull();
});

it('sends nothing back to a zone once the draw has started', function () {
    $event = Event::factory()->create(['polling_status' => 'polling']);
    $row = EventRegistration::factory()->create(['event_id' => $event->id]);

    $this->actingAs(superAdmin(), 'admin')->get(route('admin.events.registrations.index', $event))
        ->assertDontSee(route('admin.events.registrations.return', [$event, $row]));
    $this->post(route('admin.events.registrations.return', [$event, $row]), ['hq_note' => 'Fix it'])
        ->assertSessionHas('error', 'The draw has started: a row can no longer go back to its zone.');
    expect($row->fresh()->hq_note)->toBeNull()->and($row->fresh()->verified_at)->not->toBeNull();
});
