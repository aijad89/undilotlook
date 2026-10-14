<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Support\PollingGuard;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateEventStatuses extends Command
{
    protected $signature = 'events:update-statuses';
    protected $description = 'Automatically transition event statuses based on date fields';

    public function handle(): int
    {
        // Step 1: end_date passed → completed (highest priority, always)
        $completed = Event::whereIn('status', ['open', 'closed'])
            ->where('end_date', '<=', now())
            ->update(['status' => 'completed']);

        // Step 2: registration_close_at passed → closed (only if auto_close enabled)
        $closed = Event::where('status', 'open')
            ->where('auto_close', true)
            ->where('registration_close_at', '<=', now())
            ->where('end_date', '>', now())
            ->update(['status' => 'closed']);

        // Step 3: registration_open_at reached → open (only if auto_open enabled)
        $opened = Event::where('status', 'draft')
            ->where('auto_open', true)
            ->where('registration_open_at', '<=', now())
            ->update(['status' => 'open']);

        // Step 4: polling_start_date reached → polling (only if auto_polling_open enabled)
        //
        // One at a time, not a mass update, because a draw has to EARN the open.
        // This was `->update(['polling_status' => 'polling'])` over the whole
        // matching set, so every rule the Start Polling button enforces — plots
        // exist, every approved registration carries a craft code, those codes
        // have lots to win, the booth sizes have the lots for them — was skipped
        // entirely by the automatic door. The button refused the event; a minute
        // later cron opened it, and the first participant to press Confirm was
        // the one who found out.
        $pollingOpened = 0;

        $due = Event::where('polling_status', 'draft')
            ->where('auto_polling_open', true)
            ->whereNotNull('polling_start_date')
            ->where('polling_start_date', '<=', now())
            ->get();

        foreach ($due as $event) {
            if ($blocker = PollingGuard::blocker($event)) {
                // Left in draft on purpose: the switch stays on, so it opens by
                // itself the moment somebody fixes what is missing.
                Log::warning('Auto-open skipped a lot draw that is not ready', [
                    'event_id' => $event->id,
                    'title'    => $event->title,
                    'reason'   => $blocker,
                ]);

                $this->warn("Event {$event->id} not opened: {$blocker}");

                continue;
            }

            $event->update(['polling_status' => 'polling']);
            $pollingOpened++;
        }

        // Step 5: polling_deadline passed → closed (only if auto_polling_close enabled)
        $pollingClosed = Event::where('polling_status', 'polling')
            ->where('auto_polling_close', true)
            ->where('polling_deadline', '<=', now())
            ->whereNotNull('polling_deadline')
            ->update(['polling_status' => 'closed']);

        $this->info("Event statuses updated: {$opened} published, {$closed} closed, {$completed} completed.");
        $this->info("Polling statuses updated: {$pollingOpened} opened, {$pollingClosed} closed.");

        return Command::SUCCESS;
    }
}
