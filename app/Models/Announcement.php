<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A broadcast, and the record of who it went to.
 *
 * Each recipient gets their own row in `notifications`, carrying this row's id
 * as `data->announcement_id`; that is how the show page counts read and unread,
 * and how a banner or attachment is checked against who may open it.
 *
 * `sent_to` is a string, not a foreign key: "all_users", "all_admins" or
 * "admin_<id>".
 */
class Announcement extends Model
{
    public const TYPES = [
        'news' => 'News',
        'alert' => 'Alert',
        'system' => 'System',
    ];

    protected $fillable = [
        'title',
        'message',
        'type',
        'sent_to',
        'banner',
        'attachment',
    ];

    /**
     * Who this admin may send to. Broadcasts to everyone are a super admin's;
     * the form and the validation both read this, so neither offers what the
     * other would refuse.
     *
     * @return array<string, string> value => label
     */
    public static function recipientsFor(Admin $admin): array
    {
        $everyone = $admin->isSuperAdmin() ? [
            'all_users' => 'Every participant',
            'all_admins' => 'Every admin',
        ] : [];

        return $everyone + ['me' => 'Myself (a test)'];
    }

    /**
     * May this admin see this announcement — the page, its banner, its file?
     * Global admins see every one; anyone else, what was addressed to them.
     */
    public function visibleToAdmin(Admin $admin): bool
    {
        return $admin->hasGlobalAccess()
            || in_array($this->sent_to, ['all_admins', 'admin_'.$admin->id], true);
    }

    /** May this participant open its banner or file? Only if it reached them. */
    public function reached(User $user): bool
    {
        return $user->notifications()->where('data->announcement_id', $this->id)->exists();
    }

    public function sentToLabel(): string
    {
        return match (true) {
            $this->sent_to === 'all_users' => 'Every participant',
            $this->sent_to === 'all_admins' => 'Every admin',
            str_starts_with((string) $this->sent_to, 'admin_') => 'One admin',
            default => 'Unknown',
        };
    }
}
