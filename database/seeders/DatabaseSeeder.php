<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Not a data seeder — a split.
 *
 * ABOVE THE LINE: what an empty installation needs before anyone can use it.
 * Right now that is one super admin, because every screen that creates an admin
 * is behind the admin login.
 *
 * BELOW THE LINE: demo and test data, which must never run on a live box. There
 * is none yet. When there is, it goes behind the `isProduction()` guard below
 * and is named so somebody can still plant it on purpose with
 * `db:seed --class=…`.
 *
 * THE GUARD USES isProduction(), the same line AppServiceProvider uses to decide
 * whether Eloquent runs in strict mode. One definition of "this is the real box",
 * not two.
 *
 * The reason it exists: in the system this came from, a bare `db:seed` on a live
 * server created nineteen admin logins with a four-digit password. Nothing had
 * refused; the seeder simply did what it was told.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(AdminSeeder::class);

        if (app()->isProduction()) {
            $this->command?->newLine();
            $this->command?->warn('Production: reference data only. No demo data was planted.');

            return;
        }

        // Demo and test data goes here, behind the guard.
    }
}
