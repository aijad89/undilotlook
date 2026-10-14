<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\Poll;
use App\Notifications\VotingCloseReminder;
use Illuminate\Console\Command;

class SendVotingReminder extends Command
{
    protected $signature = 'voting:send-reminder';

    protected $description = 'Remind everyone who has not confirmed a lot, 30 minutes before the draw closes';

    public function handle(): int
    {
        $events = Event::where('polling_status', 'polling')
            ->where('polling_deadline', '<=', now()->addMinutes(35))
            ->where('polling_deadline', '>', now()->addMinutes(25))
            ->get();

        foreach ($events as $event) {
            // "Has drawn" is asked of polls, by account.
            $drawn = Poll::where('event_id', $event->id)->pluck('user_id');

            /*
             * This runs every minute and the window spans ten, so it needs an
             * "already reminded" marker — and the marker must be written
             * SYNCHRONOUSLY. The notification is queued: a guard that asked the
             * `notifications` table could not see its own writes until a worker
             * ran, and re-queued everybody on every tick. `reminded_at` is stamped
             * here, in the same run that queues the reminder.
             *
             * A company with no account yet has nobody to remind.
             */
            $registrations = $event->registrations()
                ->whereNotNull('user_id')
                ->whereNotIn('user_id', $drawn)
                ->whereNull('reminded_at')
                ->with('user')
                ->get();

            foreach ($registrations as $reg) {
                $reg->user->notify(new VotingCloseReminder($event));

                // After the queue write, per row: if notify() throws, the row stays
                // unmarked and the next tick retries it. Quietly, because a
                // reminder stamp is not a change anyone audits.
                $reg->forceFill(['reminded_at' => now()])->saveQuietly();
            }

            $this->info("Sent {$registrations->count()} reminder(s) for {$event->title}.");
        }

        return self::SUCCESS;
    }
}
