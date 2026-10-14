<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Company;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * The browser suite's starting state, planted into `undilot_e2e` by
 * tests/e2e/serve.js and nowhere else.
 *
 * Every account gets a random password: the suite signs in through /auto, so
 * nothing here is a password anyone could reuse. Emails are on `e2e.test`, a
 * domain nothing real is ever on.
 *
 * The event's draw list is filled; its lot plan is NOT — building the plan
 * through the screens is what the draw spec is for.
 */
class E2eSeeder extends Seeder
{
    public function run(): void
    {
        abort_unless(app()->environment(['e2e', 'testing']), 500, 'E2eSeeder plants fixtures; it runs only in e2e.');

        $this->call(KrafCodeSeeder::class);

        $password = fn () => bcrypt(Str::random(40));

        foreach ([['super', null], ['hq', null], ['admin', 'Johor']] as [$role, $zone]) {
            Admin::create([
                'name' => 'E2E '.ucfirst($role),
                'email' => "e2e-{$role}@e2e.test",
                'password' => $password(),
                'role' => $role,
                'state' => $zone,
            ]);
        }

        $event = Event::create([
            'title' => 'E2E Fair',
            'location' => 'Dewan Ujian',
            'state' => 'Johor',
            'status' => 'open',
            'start_date' => now()->addMonth(),
            'end_date' => now()->addMonth()->addDays(3),
            'registration_open_at' => now()->subWeek(),
            'registration_close_at' => now()->addWeeks(2),
            'max_participants' => 50,
        ]);

        // Three companies, three codes, two booth sizes — the shape of a real draw.
        foreach ([
            ['Tenun E2E', 'Johor', 'tenun', 'TTN', '3x3', '880101-01-5555'],
            ['Logam E2E', 'Perak', 'logam', 'LEH', '3x3', null],
            ['Kayu E2E', 'Kedah', 'kayu', 'RKP', '3x6', '870202-02-5555'],
        ] as [$name, $zone, $handle, $code, $size, $ic]) {
            $user = User::create([
                'name' => "Owner {$name}",
                'email' => "{$handle}@e2e.test",
                'password' => $password(),
                'ic_number' => $ic,
                'is_active' => true,
            ]);

            $company = Company::create(['user_id' => $user->id, 'name' => $name, 'state' => $zone, 'is_active' => true]);

            EventRegistration::create([
                'event_id' => $event->id,
                'company_id' => $company->id,
                'user_id' => $user->id,
                'code' => $code,
                'plot_size' => $size,
            ]);
        }
    }
}
