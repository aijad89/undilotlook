<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * HQ took the company off a draw it had been told it is in, and why. Sent only
 * for a company that heard "you are in" — one taken off before HQ verified it
 * never heard anything, and hears nothing now. Carries `join_event_id`, like
 * JoinRequestAnswered: the next step is the event page, not Confirm Lot.
 */
class RemovedFromDraw extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Event $event, public string $reason)
    {
        // Sent from inside the office's transaction: only once it holds.
        $this->afterCommit();
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Taken off the draw list: '.$this->event->title)
            ->greeting('Hi '.$notifiable->name.',')
            ->line('The office took your company off the draw list for '.$this->event->title.'.')
            ->line('Reason: '.$this->reason)
            ->action('Open the event', route('events.show', $this->event))
            ->line('You may ask to join again while registration is open.');
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'Taken off the draw list',
            'join_event_id' => $this->event->id,
            'approved' => false,
            'event_name' => $this->event->title,
            'message' => 'Your company was taken off the draw list for '.$this->event->title.".\nReason: ".$this->reason,
            'type' => 'alert',
        ];
    }
}
