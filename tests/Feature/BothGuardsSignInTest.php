<?php

use App\Models\Admin;
use App\Models\User;

/*
 * PHASE 1'S DONE-CRITERION, AS A TEST.
 *
 * Two session guards is the shaping decision of this application, and almost
 * everything awkward about its auth follows from it: an admin is a guest on the
 * `web` guard and a participant is a guest on `admin`, so Laravel's single
 * `redirectTo` is wrong for one of them whichever way it is set.
 *
 * bootstrap/app.php answers that with two closures. These tests are what stop
 * either of them being quietly broken — the failure they prevent is not a 404 on
 * one page but a redirect loop, or a signed-in admin staring at the participant
 * login form, which is what happened in the system this came from.
 */

it('signs an admin in and lands them on the admin dashboard', function () {
    $admin = Admin::factory()->super()->create(['password' => 'kraf1234']);

    $this->post('/admin/login', [
        'email' => $admin->email,
        'password' => 'kraf1234',
    ])->assertRedirect('/admin/dashboard');

    expect(auth('admin')->check())->toBeTrue()
        ->and(auth('web')->check())->toBeFalse();
});

it('signs a participant in and lands them on their dashboard', function () {
    $user = User::factory()->create(['password' => 'kraf1234']);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'kraf1234',
    ])->assertRedirect('/');

    expect(auth('web')->check())->toBeTrue()
        ->and(auth('admin')->check())->toBeFalse();
});

it('sends a signed-out visitor of an admin URL to the ADMIN login, not the participant one', function () {
    // The half of redirectGuestsTo that Laravel's default gets wrong: its
    // fallback is always `login`, whichever guard the route belongs to.
    $this->get('/admin/dashboard')->assertRedirect(route('admin.login'));
    $this->get('/dashboard')->assertRedirect(route('login'));
});

it('sends an already signed-in admin away from the admin login to the admin dashboard', function () {
    // The other direction, and the one that is easy to miss. Laravel's default
    // target is /dashboard, which is behind the `web` guard — where an
    // admin-guard session is a guest. Without redirectUsersTo, `guest:admin`
    // bounces the admin to /dashboard, redirectGuestsTo then bounces them to
    // route('login'), and a signed-in admin ends up looking at the PARTICIPANT
    // login form. Two clicks on Sign In was enough to produce it.
    $admin = Admin::factory()->super()->create();

    $this->actingAs($admin, 'admin')
        ->get('/admin/login')
        ->assertRedirect(route('admin.dashboard'));
});

it('logs out a participant whose account was switched off, mid-session', function () {
    // `is_active` is the switch, not `email_verified_at`: accounts are created by
    // an admin, so there is nothing to verify. EnforceUserIsActive is what makes
    // switching it off immediate rather than taking effect at the next login.
    $user = User::factory()->create();

    $this->actingAs($user)->get('/dashboard')->assertOk();

    $user->update(['is_active' => false]);

    $this->get('/dashboard')->assertRedirect(route('login'));
    expect(auth('web')->check())->toBeFalse();
});

it('refuses a wrong password on both guards', function () {
    $admin = Admin::factory()->super()->create(['password' => 'kraf1234']);
    $user = User::factory()->create(['password' => 'kraf1234']);

    $this->post('/admin/login', ['email' => $admin->email, 'password' => 'wrong'])
        ->assertSessionHasErrors('email');
    expect(auth('admin')->check())->toBeFalse();

    $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])
        ->assertSessionHasErrors('email');
    expect(auth('web')->check())->toBeFalse();
});
