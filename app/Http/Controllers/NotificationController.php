<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * The participant's inbox: announcements and reminders that reached them.
 */
class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()->notifications()->latest()->paginate(15);

        // Banners and files live on the announcement, not in the notification's
        // data — loaded once for the page rather than per row.
        $announcements = Announcement::whereIn(
            'id',
            $notifications->getCollection()->pluck('data.announcement_id')->filter()->unique()
        )->get()->keyBy('id');

        return view('notifications.index', compact('notifications', 'announcements'));
    }

    public function read(Request $request, string $id)
    {
        $request->user()->notifications()->findOrFail($id)->markAsRead();

        return back();
    }

    /**
     * One statement. `unreadNotifications()` WITH the parentheses is a relation:
     * one UPDATE carrying the notifiable constraint and updated_at. Without them
     * it loads every row and saves each.
     */
    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back();
    }

    /**
     * An announcement's banner or attachment, from the private disk, to whoever
     * it reached — a participant it was sent to, or an admin allowed to see it.
     */
    public function file(Request $request, Announcement $announcement, string $kind)
    {
        $path = match ($kind) {
            'banner' => $announcement->banner,
            'attachment' => $announcement->attachment,
            default => null,
        };

        abort_unless($path && Storage::disk('local')->exists($path), 404);

        $admin = $request->user('admin');
        $user = $request->user('web');

        abort_unless(
            ($admin && $announcement->visibleToAdmin($admin)) || ($user && $announcement->reached($user)),
            403
        );

        return Storage::disk('local')->response($path);
    }
}
