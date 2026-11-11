<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The office's answer on an event's draw list: the company is in (its request
 * approved, or added by the office directly), or its request was not approved,
 * with the reason. Carries `join_event_id`, not `event_id`: the notifications
 * page offers Confirm Lot for an `event_id`, and that is not the next step here.
 */
class JoinRequestAnswered extends Notification implements ShouldQueue
{
    use Queueable;

    /** A reason means not approved; none means the company is in the draw. */
    public function __construct(public Event $event, public ?string $rejectReason = null)
    {
        // Sent from inside the office's transaction: only once it holds.
        $this->afterCommit();
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    private function approved(): bool
    {
        return $this->rejectReason === null;
    }

    public function toMail($notifiable): MailMessage
    {
        $mail = (new MailMessage)->greeting('Hi '.$notifiable->name.',');

        return $this->approved()
            ? $mail->subject('You are in the draw: '.$this->event->title)
                ->line('Your company is in the lot draw for '.$this->event->title.'.')
                ->action('Open dashboard', route('dashboard'))
            : $mail->subject('Request not approved: '.$this->event->title)
                ->line('The office did not approve your request to join '.$this->event->title.'.')
                ->line('Reason: '.$this->rejectReason)
                ->action('Change and send again', route('events.show', $this->event))
                ->line('You can send it again while registration is open.');
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => $this->approved() ? 'You are in the draw' : 'Request not approved',
            'join_event_id' => $this->event->id,
            'approved' => $this->approved(),
            'event_name' => $this->event->title,
            'message' => $this->approved()
                ? 'Your company is in the lot draw for '.$this->event->title.'.'
                : 'Your request to join '.$this->event->title." was not approved.\nReason: ".$this->rejectReason,
            'type' => $this->approved() ? 'success' : 'alert',
        ];
    }
}
