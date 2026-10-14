<?php

use App\Models\Announcement;
use App\Models\Company;
use App\Models\User;
use App\Notifications\AnnouncementNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

/**
 * Announcements: super/hq send, everyone reads what reached them, and a
 * banner or file goes only to someone the announcement reached.
 */
it('queues one notification per active participant, and says queued rather than sent', function () {
    Notification::fake();
    $active = Company::factory()->count(2)->create()->map->user;
    $inactive = User::factory()->inactive()->create();

    $this->actingAs(superAdmin(), 'admin')
        ->post(route('admin.announcements.store'), [
            'type' => 'news', 'title' => 'Pameran dibuka', 'message' => 'Selamat datang.', 'recipient_type' => 'all_users',
        ])
        ->assertRedirect(route('admin.announcements.index'))
        ->assertSessionHas('success', fn ($m) => str_starts_with($m, 'Queued for 2 recipient(s)'));

    Notification::assertSentTo($active, AnnouncementNotification::class);
    Notification::assertNotSentTo($inactive, AnnouncementNotification::class);
    expect(Announcement::sole()->sent_to)->toBe('all_users');
});

it('lets only super and hq send, and only a super broadcast to everyone', function () {
    $hq = App\Models\Admin::factory()->hq()->create();
    $post = ['type' => 'news', 'title' => 'T', 'message' => 'M', 'recipient_type' => 'all_users'];

    $this->actingAs(stateAdmin('Johor'), 'admin')->get(route('admin.announcements.create'))->assertForbidden();
    $this->actingAs(stateAdmin('Johor'), 'admin')->post(route('admin.announcements.store'), $post)->assertForbidden();

    $this->actingAs($hq, 'admin')->post(route('admin.announcements.store'), $post)->assertSessionHasErrors('recipient_type');
    expect(Announcement::count())->toBe(0);
});

it('shows a participant their inbox, marks it read, and counts the bell', function () {
    $user = Company::factory()->create()->user;
    $user->notify(new AnnouncementNotification('Hello', 'Body text', 'news', 1));

    $this->actingAs($user)->get(route('dashboard'))->assertSee('1 unread');
    $this->actingAs($user)->get(route('notifications.index'))->assertOk()->assertSee('Body text');

    $this->actingAs($user)->post(route('notifications.read-all'));

    expect($user->unreadNotifications()->count())->toBe(0);
});

it('serves a banner to whoever the announcement reached, and to no one else', function () {
    Storage::fake('local');
    $admin = superAdmin();
    $reached = Company::factory()->create()->user;
    $outsider = User::factory()->inactive()->create();   // active users all receive a broadcast

    $this->actingAs($admin, 'admin')->post(route('admin.announcements.store'), [
        'type' => 'news', 'title' => 'T', 'message' => 'M', 'recipient_type' => 'all_users',
        'banner' => UploadedFile::fake()->image('banner.jpg'),
    ]);

    $announcement = Announcement::sole();
    Storage::disk('local')->assertExists($announcement->banner);

    // The suite's queue is sync, so the broadcast above wrote real rows.
    expect($announcement->reached($reached))->toBeTrue()
        ->and($announcement->reached($outsider))->toBeFalse();

    // actingAs() persists across requests in one test; without this the super
    // admin from the store above is still signed in and answers for everyone.
    auth()->forgetGuards();

    $this->actingAs($reached, 'web')->get(route('announcements.file', [$announcement, 'banner']))->assertOk();
    $this->actingAs($outsider, 'web')->get(route('announcements.file', [$announcement, 'banner']))->assertForbidden();
    $this->actingAs(stateAdmin('Johor'), 'admin')->get(route('announcements.file', [$announcement, 'banner']))->assertForbidden();
    $this->actingAs($admin, 'admin')->get(route('announcements.file', [$announcement, 'banner']))->assertOk();
});

it('hides an announcement from an admin it was not addressed to, in the list and by id', function () {
    $note = Announcement::create(['title' => 'Private note', 'message' => 'M', 'type' => 'system', 'sent_to' => 'admin_999']);
    $state = stateAdmin('Johor');

    $this->actingAs($state, 'admin')->get(route('admin.announcements.index'))->assertOk()->assertDontSee('Private note');
    $this->actingAs($state, 'admin')->get(route('admin.announcements.show', $note))->assertForbidden();
    $this->actingAs(superAdmin(), 'admin')->get(route('admin.announcements.show', $note))->assertOk();
});
