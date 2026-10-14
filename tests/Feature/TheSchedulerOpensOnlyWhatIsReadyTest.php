<?php

use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Plot;
use App\Models\Poll;
use App\Notifications\VotingCloseReminder;
use Illuminate\Support\Facades\Notification;

/**
 * Auto open and close are wanted here (daftar switched them off). The scheduler
 * is a second door into "polling", and it must ask PollingGuard exactly as the
 * button does — or cron opens the draw the button refused.
 */
function dueDraw(array $attributes = []): Event
{
    return Event::factory()->create($attributes + [
        'polling_status' => 'draft',
        'auto_polling_open' => true,
        'polling_start_date' => now()->subMinute(),
        'polling_deadline' => now()->addDay(),
    ]);
}

it('opens a due draw that is ready, and leaves one that is not in draft', function () {
    $ready = dueDraw();
    Plot::factory()->create(['event_id' => $ready->id]);
    EventRegistration::factory()->coded('RKP')->create(['event_id' => $ready->id]);

    $noPlots = dueDraw();
    EventRegistration::factory()->coded('RKP')->create(['event_id' => $noPlots->id]);

    $switchedOff = dueDraw(['auto_polling_open' => false]);
    Plot::factory()->create(['event_id' => $switchedOff->id]);

    $this->artisan('events:update-statuses')->assertSuccessful();

    expect($ready->fresh()->polling_status)->toBe('polling')
        ->and($noPlots->fresh()->polling_status)->toBe('draft')
        ->and($switchedOff->fresh()->polling_status)->toBe('draft');
});

it('closes voting at the deadline when asked to, and not otherwise', function () {
    $auto = Event::factory()->create(['polling_status' => 'polling', 'auto_polling_close' => true, 'polling_deadline' => now()->subMinute()]);
    $manual = Event::factory()->create(['polling_status' => 'polling', 'auto_polling_close' => false, 'polling_deadline' => now()->subMinute()]);
    $notYet = Event::factory()->create(['polling_status' => 'polling', 'auto_polling_close' => true, 'polling_deadline' => now()->addHour()]);

    $this->artisan('events:update-statuses');

    expect($auto->fresh()->polling_status)->toBe('closed')
        ->and($manual->fresh()->polling_status)->toBe('polling')
        ->and($notYet->fresh()->polling_status)->toBe('polling');
});

it('reads the voting window on Malaysian time, as the admin typed it', function () {
    // 09:00 typed means 09:00 here. On UTC the draw would open at 17:00.
    $this->travelTo(now()->setTimezone('Asia/Kuala_Lumpur')->setTime(9, 0, 30));

    $event = dueDraw(['polling_start_date' => now()->setTime(9, 0)]);
    Plot::factory()->create(['event_id' => $event->id]);
    EventRegistration::factory()->coded('RKP')->create(['event_id' => $event->id]);

    expect(config('app.timezone'))->toBe('Asia/Kuala_Lumpur');

    $this->artisan('events:update-statuses');

    expect($event->fresh()->polling_status)->toBe('polling');
});

it('reminds everyone who has not confirmed, once, half an hour before the close', function () {
    Notification::fake();

    $event = Event::factory()->create(['polling_status' => 'polling', 'polling_deadline' => now()->addMinutes(30)]);
    $waiting = EventRegistration::factory()->create(['event_id' => $event->id]);
    $drawn = EventRegistration::factory()->create(['event_id' => $event->id]);
    $noAccount = EventRegistration::factory()->create(['event_id' => $event->id, 'user_id' => null]);
    Poll::create(['event_id' => $event->id, 'user_id' => $drawn->user_id, 'code' => null]);

    $this->artisan('voting:send-reminder')->assertSuccessful();
    $this->artisan('voting:send-reminder')->assertSuccessful();

    Notification::assertSentToTimes($waiting->user, VotingCloseReminder::class, 1);
    Notification::assertNotSentTo($drawn->user, VotingCloseReminder::class);

    expect($waiting->fresh()->reminded_at)->not->toBeNull()
        ->and($noAccount->fresh()->reminded_at)->toBeNull();
});

it('registers both commands to run every minute', function () {
    $events = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())
        ->map(fn ($e) => [$e->command, $e->expression]);

    expect($events->filter(fn ($e) => str_contains($e[0], 'events:update-statuses') && $e[1] === '* * * * *'))->toHaveCount(1)
        ->and($events->filter(fn ($e) => str_contains($e[0], 'voting:send-reminder') && $e[1] === '* * * * *'))->toHaveCount(1);
});
