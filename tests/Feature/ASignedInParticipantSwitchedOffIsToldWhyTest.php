<?php

use App\Models\Company;

it('tells a participant whose account is switched off mid-session why they were signed out', function () {
    $user = Company::factory()->create()->user;
    $this->actingAs($user, 'web');
    $user->update(['is_active' => false]);

    $this->followingRedirects()->get(route('dashboard'))
        ->assertSee('Your account has been deactivated. Please contact the administrator.');
    $this->assertGuest('web');
});

it('tells a participant whose company is switched off mid-session why they were signed out', function () {
    $company = Company::factory()->create();
    $this->actingAs($company->user, 'web');
    $company->update(['is_active' => false]);

    $this->followingRedirects()->get(route('dashboard'))
        ->assertSee('Your company has been deactivated. Please contact the administrator.');
    $this->assertGuest('web');
});
