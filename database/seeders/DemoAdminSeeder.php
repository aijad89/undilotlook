<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Support\AdminZones;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * DEMO: one state admin per zone (14), plus one HQ admin.
 *
 * Emails are `admin.<zone>@demo.test`. Every password is random and thrown
 * away — sign in as any of them through /auto, which exists only locally.
 * Re-runnable: matched on email.
 */
class DemoAdminSeeder extends Seeder
{
    public function run(): void
    {
        $this->admin('hq@demo.test', 'Demo HQ Admin', 'hq', null);

        foreach (AdminZones::DEFAULTS as $zone) {
            $this->admin('admin.'.Str::slug($zone).'@demo.test', "Demo Admin {$zone}", 'admin', $zone);
        }
    }

    private function admin(string $email, string $name, string $role, ?string $zone): void
    {
        Admin::firstOrCreate(['email' => $email], [
            'name' => $name,
            'password' => Str::random(40),
            'role' => $role,
            'state' => $zone,
        ]);
    }
}
