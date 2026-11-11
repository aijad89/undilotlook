<?php

namespace App\Notifications;

use App\Models\EventJoinRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The office's answer to a request to join: approved onto the draw list, or
 * not approved with its reason. Carries `join_event_id`, not `event_id`: the
 * notifications page offers Confirm Lot for an `event_id`, and that is not the
 * next step here.
 */
class JoinRequestAnswered extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public EventJoinRequest $joinRequest)
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
        return $this->joinRequest->status === 'approved';
    }

    public function toMail($notifiable): MailMessage
    {
        $event = $this->joinRequest->event;
        $mail = (new MailMessage)->greeting('Hi '.$notifiable->name.',');

        return $this->approved()
            ? $mail->subject('You are in the draw: '.$event->title)
                ->line('The office approved your request to join '.$event->title.'. Your company is in the lot draw.')
                ->action('Open dashboard', route('dashboard'))
            : $mail->subject('Request not approved: '.$event->title)
                ->line('The office did not approve your request to join '.$event->title.'.')
                ->line('Reason: '.$this->joinRequest->reject_reason)
                ->action('Change and send again', route('events.show', $event))
                ->line('You can send it again while registration is open.');
    }

    public function toArray($notifiable): array
    {
        $event = $this->joinRequest->event;

        return [
            'title' => $this->approved() ? 'Request approved' : 'Request not approved',
            'join_event_id' => $event->id,
            'approved' => $this->approved(),
            'event_name' => $event->title,
            'message' => $this->approved()
                ? 'Your company is in the lot draw for '.$event->title.'.'
                : 'Your request to join '.$event->title." was not approved.\nReason: ".$this->joinRequest->reject_reason,
            'type' => $this->approved() ? 'success' : 'alert',
        ];
    }
}
