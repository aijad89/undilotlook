<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Announcement;
use App\Models\User;
use App\Notifications\AnnouncementNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Announcements. Reading is open to every admin and narrows itself through
 * Announcement::visibleToAdmin(); SENDING is super/hq only, gated in
 * routes/web.php — sending is not a state admin's job, whoever receives it.
 */
class AnnouncementController extends Controller
{
    public function index(Request $request)
    {
        $admin = $request->user('admin');

        $announcements = Announcement::latest()
            ->when($request->input('type'), fn ($q, $type) => $q->where('type', $type))
            // The list asks what show() asks, so nothing is listed that cannot be opened.
            ->unless($admin->hasGlobalAccess(), fn ($q) => $q->whereIn('sent_to', ['all_admins', 'admin_'.$admin->id]))
            ->paginate(15)
            ->withQueryString();

        return view('admin.announcements.index', [
            'announcements' => $announcements,
            'canSend' => $admin->hasGlobalAccess(),
        ]);
    }

    public function create(Request $request)
    {
        return view('admin.announcements.create', [
            'recipients' => Announcement::recipientsFor($request->user('admin')),
        ]);
    }

    public function store(Request $request)
    {
        $admin = $request->user('admin');

        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(Announcement::TYPES))],
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
            'recipient_type' => ['required', Rule::in(array_keys(Announcement::recipientsFor($admin)))],
            'banner' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,zip|max:2048',
        ]);

        // The private disk: a file is served only to whoever the announcement
        // reached (AnnouncementFileController). Outside the transaction, because
        // storage has no rollback.
        $banner = $request->file('banner')?->store('announcements', 'local');
        $attachment = $request->file('attachment')?->store('announcements/files', 'local');

        [$sentTo, $recipients] = match ($data['recipient_type']) {
            'all_users' => ['all_users', User::where('is_active', true)->cursor()],
            'all_admins' => ['all_admins', Admin::cursor()],
            default => ['admin_'.$admin->id, [$admin]],
        };

        $count = 0;

        // The row and its fan-out are one act. Committed apart, a timeout part-way
        // left an announcement everybody could see that only some had been queued
        // for — and pressing Send again re-notified everyone already reached.
        DB::transaction(function () use ($data, $sentTo, $banner, $attachment, $recipients, &$count) {
            $announcement = Announcement::create([
                'title' => $data['title'],
                'message' => $data['message'],
                'type' => $data['type'],
                'sent_to' => $sentTo,
                'banner' => $banner,
                'attachment' => $attachment,
            ]);

            foreach ($recipients as $recipient) {
                $recipient->notify(new AnnouncementNotification($data['title'], $data['message'], $data['type'], $announcement->id));
                $count++;
            }
        });

        // "Queued", not "sent": nothing is delivered until the queue worker runs.
        return redirect()->route('admin.announcements.index')
            ->with('success', "Queued for {$count} recipient(s). Delivery runs on the queue worker.");
    }

    public function show(Request $request, Announcement $announcement)
    {
        abort_unless($announcement->visibleToAdmin($request->user('admin')), 403);

        $delivered = DB::table('notifications')->where('data->announcement_id', $announcement->id);

        return view('admin.announcements.show', [
            'announcement' => $announcement,
            'delivered' => (clone $delivered)->count(),
            'read' => (clone $delivered)->whereNotNull('read_at')->count(),
            'queued' => DB::table('jobs')->where('payload', 'like', '%AnnouncementNotification%')->count(),
        ]);
    }
}
