<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * One announcement reaching one recipient. Queued: a broadcast walks every
 * account, and a synchronous notify() per row would hold the request open.
 *
 * `daftar` had three copies of this class, one per type, differing only in name.
 * The type travels in the data instead.
 */
class AnnouncementNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $message,
        public string $type,
        public int $announcementId,
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->type,
            'announcement_id' => $this->announcementId,
        ];
    }
}
