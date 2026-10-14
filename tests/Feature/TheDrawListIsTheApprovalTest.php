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

    $this->actingAs(stateAdmin('Johor'), 'admin')
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
            'action' => 'remove',
            'registration_id' => [$holder->id, $other->id],
        ])
        ->assertRedirect();

    expect($holder->fresh())->not->toBeNull()
        ->and($other->fresh())->toBeNull();
});

it('puts back exactly what the last removal took', function () {
    $event = Event::factory()->create();
    $company = Company::factory()->create(['state' => 'Johor']);

    EventRegistration::factory()->coded('TBPS')->create([
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
