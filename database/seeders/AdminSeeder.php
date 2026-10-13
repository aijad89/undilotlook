<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * The first super admin, and nothing else.
 *
 * WHY A SEEDER AT ALL. Every screen that creates an admin is itself behind the
 * admin login, so there is no way in without one account already existing. This
 * is that account; it is not demo data.
 *
 * THE PASSWORD COMES FROM `.env` AND HAS NO DEFAULT, deliberately.
 *
 * The system this repo came from shipped a seeder with `const PASSWORD = '1234'`
 * and nineteen accounts using it. Nothing in the code stopped those reaching a
 * live box — the only thing between them and production was somebody remembering
 * not to run `db:seed` there. A constant in a tracked file is a password in the
 * git history on the day it is written.
 *
 * So there is no fallback here. A missing `ADMIN_SEED_PASSWORD` stops the seeder
 * with an explanation rather than quietly planting a weak account, because a
 * weak account that works is harder to notice than a seeder that refuses.
 *
 * IDEMPOTENT. Running it again updates the name and role of the existing row and
 * leaves the password alone — re-seeding must not silently reset a password
 * somebody has since changed.
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_SEED_EMAIL');
        $password = env('ADMIN_SEED_PASSWORD');

        if (blank($email) || blank($password)) {
            throw new RuntimeException(
                'AdminSeeder needs ADMIN_SEED_EMAIL and ADMIN_SEED_PASSWORD in .env. '
                .'There is deliberately no default: a seeded password with a fallback '
                .'value is a password in the git history.'
            );
        }

        $existing = Admin::where('email', $email)->first();

        if ($existing) {
            $existing->update([
                'name' => env('ADMIN_SEED_NAME', $existing->name),
                'role' => Admin::SUPER_ROLE,
            ]);

            $this->command?->info("Admin {$email} already exists — name and role refreshed, password untouched.");

            return;
        }

        Admin::create([
            'name' => env('ADMIN_SEED_NAME', 'Super Admin'),
            'email' => $email,
            // Hashed by the model's `password` => 'hashed' cast.
            'password' => $password,
            'role' => Admin::SUPER_ROLE,
            'state' => null,
        ]);

        $this->command?->info("Created super admin {$email}.");
    }
}
