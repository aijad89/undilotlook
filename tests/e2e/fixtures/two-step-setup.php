<?php

// An open event with an empty draw list, and one Johor company on no list, with
// an account — for the zone-then-HQ check to walk through. Prints the event id,
// the event title, the company name and the account email, one per line.

use App\Models\Company;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Str;

// Short: a long unbroken name is not what the office types, and would widen the table.
$tag = substr(uniqid(), -5);

$event = Event::create([
    'title' => "Check Fair {$tag}",
    'location' => 'Dewan Semak',
    'state' => 'Johor',
    'status' => 'open',
    'start_date' => now()->addMonth(),
    'end_date' => now()->addMonth()->addDay(),
    'registration_open_at' => now()->subDay(),
    'registration_close_at' => now()->addWeek(),
    'max_participants' => 10,
]);

$user = User::create([
    'name' => "Owner Anyaman {$tag}",
    'email' => "anyaman-{$tag}@e2e.test",
    'password' => bcrypt(Str::random(40)),
    'is_active' => true,
]);

$company = Company::create(['user_id' => $user->id, 'name' => "Anyaman {$tag}", 'state' => 'Johor', 'is_active' => true]);
$company->forceFill(['kraf_code' => 'TTN', 'plot_size' => '3x3'])->save();

echo $event->id, PHP_EOL, $event->title, PHP_EOL, $company->name, PHP_EOL, $user->email, PHP_EOL;
