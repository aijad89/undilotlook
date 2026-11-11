<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * A participant's profile: their account and company as the office keeps them,
 * read-only, and the one thing they change themselves, their password.
 */
function profileOwner(): Company
{
    $user = User::factory()->create([
        'name' => 'Siti Aminah', 'email' => 'siti@kraf.test', 'phone' => '012-345 6789',
        'ic_number' => '800101-14-5678', 'password' => 'lama1234',
    ]);

    return Company::factory()->create(['user_id' => $user->id, 'name' => 'Seramik Klang Studio', 'state' => 'Selangor', 'plot_size' => '3x6', 'kraf_code' => 'TTK']);
}

it('shows the account and company as the office keeps them, the IC number hidden but its last four', function () {
    $company = profileOwner();

    $this->actingAs($company->user, 'web')->get(route('profile'))
        ->assertOk()
        ->assertSeeInOrder(['Your account', 'Siti Aminah', 'siti@kraf.test', '012-345 6789', '••••••-••-5678'])
        ->assertSeeInOrder(['Your company', 'Seramik Klang Studio', 'Selangor', '3x6'])
        ->assertDontSee('Your company on record')
        ->assertDontSee('800101')
        // The craft code is the office's, never shown to the participant.
        ->assertDontSee('TTK');
});

it('is reached from the menu, and only when signed in', function () {
    $this->get(route('profile'))->assertRedirect(route('login'));

    $company = profileOwner();
    $this->actingAs($company->user, 'web')->get(route('dashboard'))
        ->assertSee('href="'.route('profile').'"', false);
});

it('changes the password only with the current one, to a new and sound one', function () {
    $company = profileOwner();
    $user = $company->user;
    $this->actingAs($user, 'web');

    $this->from(route('profile'))->put(route('profile.password'), ['current_password' => 'salah999', 'password' => 'baharu123', 'password_confirmation' => 'baharu123'])
        ->assertSessionHasErrorsIn('password', ['current_password' => 'That is not your current password.']);
    $this->from(route('profile'))->put(route('profile.password'), ['current_password' => 'lama1234', 'password' => 'lama1234', 'password_confirmation' => 'lama1234'])
        ->assertSessionHasErrorsIn('password', 'password');
    $this->from(route('profile'))->put(route('profile.password'), ['current_password' => 'lama1234', 'password' => 'short', 'password_confirmation' => 'short'])
        ->assertSessionHasErrorsIn('password', 'password');
    $this->from(route('profile'))->put(route('profile.password'), ['current_password' => 'lama1234', 'password' => 'baharu123', 'password_confirmation' => 'lain12345'])
        ->assertSessionHasErrorsIn('password', 'password');
    expect(Hash::check('lama1234', $user->fresh()->password))->toBeTrue();

    $this->from(route('profile'))->put(route('profile.password'), ['current_password' => 'lama1234', 'password' => 'baharu123', 'password_confirmation' => 'baharu123'])
        ->assertRedirect(route('profile'))
        ->assertSessionHas('success');
    expect(Hash::check('baharu123', $user->fresh()->password))->toBeTrue();

    $this->get(route('profile'))->assertSee('Password changed.');
});

it('stops guessing at the current password after six tries a minute', function () {
    $company = profileOwner();
    $this->actingAs($company->user, 'web');

    foreach (range(1, 6) as $try) {
        $this->put(route('profile.password'), ['current_password' => "salah{$try}", 'password' => 'baharu123', 'password_confirmation' => 'baharu123'])
            ->assertStatus(302);
    }
    $this->put(route('profile.password'), ['current_password' => 'lama1234', 'password' => 'baharu123', 'password_confirmation' => 'baharu123'])
        ->assertStatus(429);
});
