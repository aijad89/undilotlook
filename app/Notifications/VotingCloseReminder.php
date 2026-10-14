<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Queued: until a worker runs, a sent reminder is a row in `jobs`. The command
 * that sends it marks the registration itself, so it never depends on that.
 */
class VotingCloseReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Event $event) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Reminder: the lot draw closes in 30 minutes')
            ->greeting('Hi '.$notifiable->name.',')
            ->line('The lot draw for '.$this->event->title.' closes in 30 minutes, and you have not confirmed your lot yet.')
            ->action('Confirm Lot', route('zvote.index', $this->event))
            ->line('Anyone who has not confirmed by the deadline is assigned a lot by the office.');
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'Lot draw closing soon',
            'event_id' => $this->event->id,
            'event_name' => $this->event->title,
            'message' => 'The lot draw closes in 30 minutes.',
            'type' => 'alert',
        ];
    }
}
