<?php

// One published event with registration open and nobody on its draw list, so a
// seeded participant can walk the join steps. Prints the event id.

use App\Models\Event;

$event = Event::create([
    'title' => 'Join Fair '.uniqid(),
    'location' => 'Dewan Sertai',
    'state' => 'Johor',
    'status' => 'open',
    'start_date' => now()->addMonth(),
    'end_date' => now()->addMonth()->addDay(),
    'registration_open_at' => now()->subDay(),
    'registration_close_at' => now()->addWeek(),
    'max_participants' => 10,
]);

echo $event->id, PHP_EOL;
