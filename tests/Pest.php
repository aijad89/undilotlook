<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

/*
 * RefreshDatabase IS APPLIED TO Feature BUT NOT TO Regression, and that is
 * deliberate rather than an omission.
 *
 * RefreshDatabase wraps each test in a transaction. A test that needs to plant a
 * state the schema forbids — a dangling `event_registrations.plot_id`, say, by
 * toggling `PRAGMA foreign_keys` — cannot do it inside one, because the pragma is
 * a no-op mid-transaction. Those tests live in Regression and clean up after
 * themselves.
 *
 * If you add Regression to this line, those tests will still pass. They will just
 * stop testing anything.
 */
pest()->extend(Tests\TestCase::class)->in('Feature', 'Regression', 'Unit');
uses(RefreshDatabase::class)->in('Feature');

/*
 * Shared helpers go below. Keep them few: a helper that hides which admin a test
 * is acting as makes the test shorter and the failure harder to read.
 */

/** A super admin, which is the role with no state scope. */
function superAdmin(array $attributes = []): App\Models\Admin
{
    return App\Models\Admin::factory()->create($attributes + [
        'role' => App\Models\Admin::SUPER_ROLE,
        'state' => null,
    ]);
}

/** A state admin, scoped to one zone. */
function stateAdmin(string $state = 'Johor', array $attributes = []): App\Models\Admin
{
    return App\Models\Admin::factory()->create($attributes + [
        'role' => 'admin',
        'state' => $state,
    ]);
}
