<?php

use App\Models\Company;

/**
 * /auto signs in as anyone without a password. It must work here and be
 * absent everywhere else.
 */
it('signs in as an admin or a participant with one click, dropping the other guard', function () {
    $admin = superAdmin();
    $user = Company::factory()->create()->user;

    $this->get(route('dev.auto'))->assertOk()->assertSee($admin->email)->assertSee($user->email);

    $this->post(route('dev.auto.admin', $admin))->assertRedirect(route('admin.dashboard'));
    $this->assertAuthenticatedAs($admin, 'admin');

    $this->post(route('dev.auto.user', $user))->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($user, 'web');
    $this->assertGuest('admin');
});

it('refuses outside local and testing, even with the route registered', function () {
    $admin = superAdmin();
    app()->detectEnvironment(fn () => 'production');

    $this->get('/auto')->assertNotFound();

    // Out of `testing`, CSRF is live again and would answer 419 before the
    // guard is reached. The guard is what is under test.
    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
        ->post("/auto/admin/{$admin->id}")
        ->assertNotFound();
    $this->assertGuest('admin');
});

it('offers the quick sign-in on both logins and the home page only where /auto exists', function () {
    $pages = [route('admin.login'), route('login'), route('home')];

    foreach ($pages as $page) {
        $this->get($page)->assertSee('Quick sign in')->assertSee('href="'.route('dev.auto').'"', false);
    }

    // A server never registers /auto; take it out and the button goes with it.
    $routes = new \Illuminate\Routing\RouteCollection;
    foreach (app('router')->getRoutes() as $route) {
        if (! str_starts_with((string) $route->getName(), 'dev.auto')) {
            $routes->add($route);
        }
    }
    app('router')->setRoutes($routes);

    foreach ($pages as $page) {
        $this->get($page)->assertOk()->assertDontSee('Quick sign in');
    }
});
