<?php

use App\Models\Admin;

/**
 * The admin-accounts screens. Super manages everyone; hq manages state admins
 * only — not a super, not a peer; a state admin manages nobody. The last super
 * cannot be removed, and nobody deletes themselves.
 */

function newAdminInput(array $overrides = []): array
{
    return $overrides + [
        'name' => 'Nora Johor',
        'email' => 'nora@kraftangan.test',
        'role' => 'admin',
        'state' => 'Johor',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
    ];
}

it('lists hq and super accounts together for a super, and state admins apart', function () {
    $super = superAdmin(['name' => 'Sam Super']);
    Admin::factory()->hq()->create(['name' => 'Hana HQ']);
    stateAdmin('Johor', ['name' => 'Siti State']);

    $this->actingAs($super, 'admin')->get(route('admin.admins.hq'))
        ->assertOk()->assertSee('Sam Super')->assertSee('Hana HQ')->assertDontSee('Siti State');

    $this->actingAs($super, 'admin')->get(route('admin.admins.state'))
        ->assertOk()->assertSee('Siti State')->assertDontSee('Hana HQ');
});

it('lets hq into the state list and keeps it out of the hq list', function () {
    $hq = Admin::factory()->hq()->create();

    $this->actingAs($hq, 'admin')->get(route('admin.admins.state'))->assertOk();
    $this->actingAs($hq, 'admin')->get(route('admin.admins.hq'))->assertForbidden();
    $this->actingAs($hq, 'admin')->get(route('admin.admins.create', ['group' => 'hq']))->assertForbidden();
});

it('lets hq create a state admin, and nothing above one', function () {
    $hq = Admin::factory()->hq()->create();

    $this->actingAs($hq, 'admin')->post(route('admin.admins.store'), newAdminInput())
        ->assertRedirect(route('admin.admins.state'));
    expect(Admin::where('email', 'nora@kraftangan.test')->sole())
        ->role->toBe('admin')->state->toBe('Johor');

    foreach (['hq', 'super'] as $i => $role) {
        $this->actingAs($hq, 'admin')
            ->post(route('admin.admins.store'), newAdminInput(['role' => $role, 'email' => "climb{$i}@kraftangan.test"]))
            ->assertForbidden();
    }
    expect(Admin::where('email', 'like', 'climb%')->count())->toBe(0);
});

it('keeps hq off super and hq accounts, by edit, update and delete', function () {
    $hq = Admin::factory()->hq()->create();

    foreach ([superAdmin(), Admin::factory()->hq()->create()] as $target) {
        $this->actingAs($hq, 'admin')->get(route('admin.admins.edit', $target))->assertForbidden();
        $this->actingAs($hq, 'admin')
            ->put(route('admin.admins.update', $target), newAdminInput(['email' => $target->email, 'password' => null, 'password_confirmation' => null]))
            ->assertForbidden();
        $this->actingAs($hq, 'admin')->delete(route('admin.admins.destroy', $target))->assertForbidden();

        expect($target->fresh())->not->toBeNull()->role->toBe($target->role);
    }
});

it('stops hq promoting a state admin', function () {
    $hq = Admin::factory()->hq()->create();
    $state = stateAdmin();

    $this->actingAs($hq, 'admin')
        ->put(route('admin.admins.update', $state), newAdminInput(['email' => $state->email, 'role' => 'super', 'state' => null, 'password' => null, 'password_confirmation' => null]))
        ->assertForbidden();

    expect($state->fresh()->role)->toBe('admin');
});

it('keeps a state admin out of every account screen', function () {
    $state = stateAdmin();
    $other = stateAdmin('Melaka');

    $this->actingAs($state, 'admin')->get(route('admin.admins.state'))->assertForbidden();
    $this->actingAs($state, 'admin')->get(route('admin.admins.create'))->assertForbidden();
    $this->actingAs($state, 'admin')->post(route('admin.admins.store'), newAdminInput())->assertForbidden();
    $this->actingAs($state, 'admin')->get(route('admin.admins.edit', $other))->assertForbidden();
    $this->actingAs($state, 'admin')->delete(route('admin.admins.destroy', $other))->assertForbidden();
});

it('needs a real zone for a state admin, and keeps none for hq', function () {
    $super = superAdmin();

    $this->actingAs($super, 'admin')->post(route('admin.admins.store'), newAdminInput(['state' => null]))
        ->assertSessionHasErrors('state');
    $this->actingAs($super, 'admin')->post(route('admin.admins.store'), newAdminInput(['state' => 'Atlantis']))
        ->assertSessionHasErrors('state');

    $this->actingAs($super, 'admin')->post(route('admin.admins.store'), newAdminInput(['role' => 'hq', 'state' => 'Johor']));
    expect(Admin::where('email', 'nora@kraftangan.test')->sole()->state)->toBeNull();
});

it('requires a password on create, and keeps the old one when edit leaves it blank', function () {
    $super = superAdmin();

    $this->actingAs($super, 'admin')
        ->post(route('admin.admins.store'), newAdminInput(['password' => null, 'password_confirmation' => null]))
        ->assertSessionHasErrors('password');

    $state = stateAdmin();
    $hash = $state->password;
    $this->actingAs($super, 'admin')
        ->put(route('admin.admins.update', $state), newAdminInput(['name' => 'Renamed', 'email' => $state->email, 'password' => null, 'password_confirmation' => null]))
        ->assertRedirect(route('admin.admins.state'));

    expect($state->fresh())->name->toBe('Renamed')->password->toBe($hash);
});

it('will not let the last super be demoted or anyone delete themselves', function () {
    $super = superAdmin();

    $this->actingAs($super, 'admin')
        ->from(route('admin.admins.edit', $super))
        ->put(route('admin.admins.update', $super), newAdminInput(['email' => $super->email, 'role' => 'hq', 'state' => null, 'password' => null, 'password_confirmation' => null]))
        ->assertSessionHasErrors('role');
    expect($super->fresh()->role)->toBe('super');

    // With a second super about, the last-super floor no longer stands in the
    // way — so only the self-delete check can stop this.
    superAdmin();
    $this->actingAs($super, 'admin')->delete(route('admin.admins.destroy', $super))
        ->assertSessionHasErrors();
    expect($super->fresh())->not->toBeNull();
});
