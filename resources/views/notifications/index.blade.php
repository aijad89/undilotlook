@extends('layouts.frontend')

@section('title', 'Notifications')
@section('page-title', 'Notifications')

@section('content')
@include('partials.apple-styles')

<div class="ap-page">
<div class="ap-page-header">
    <div>
        <h2 class="ap-page-title">Notifications</h2>
        <p class="ap-page-subtitle">Announcements and reminders from the office.</p>
    </div>
    @if($unreadNotificationCount > 0)
        <form method="POST" action="{{ route('notifications.read-all') }}">
            @csrf
            <button type="submit" class="ap-btn ap-btn-outline ap-btn-sm">Mark all as read</button>
        </form>
    @endif
</div>

@forelse($notifications as $notification)
    @php
        $data = $notification->data;
        $announcement = $announcements[$data['announcement_id'] ?? null] ?? null;
        $lotLink = isset($data['event_id']) ? route('zvote.index', $data['event_id']) : null;
    @endphp
    <div class="ap-card ap-card-padded mb-3" @if(! $notification->read_at) style="background:var(--ui-blue-tint);border-color:var(--ui-blue-border);" @endif>
        <div class="d-flex justify-content-between align-items-start gap-2">
            <div style="font-size:1rem;font-weight:650;color:var(--ui-text);">{{ $data['title'] ?? 'Notification' }}</div>
            <small class="text-muted text-nowrap">{{ $notification->created_at->format('d M Y, h:i A') }}</small>
        </div>

        @if($announcement?->banner)
            <img src="{{ route('announcements.file', [$announcement, 'banner']) }}" alt=""
                 style="max-width:100%;border-radius:8px;margin:10px 0;" loading="lazy">
        @endif

        <div class="mt-1" style="white-space:pre-line;font-size:1rem;color:var(--ui-text-2);">{{ $data['message'] ?? '' }}</div>

        <div class="d-flex gap-2 mt-2 flex-wrap">
            @if($announcement?->attachment)
                <a href="{{ route('announcements.file', [$announcement, 'attachment']) }}" class="ap-btn ap-btn-outline ap-btn-sm" target="_blank" rel="noopener">
                    <i class="bi bi-paperclip"></i> Attachment
                </a>
            @endif
            @if($lotLink)
                <a href="{{ $lotLink }}" class="ap-btn ap-btn-primary ap-btn-sm">Confirm Lot</a>
            @endif
            @unless($notification->read_at)
                <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                    @csrf
                    <button type="submit" class="ap-btn ap-btn-outline ap-btn-sm">Mark as read</button>
                </form>
            @endunless
        </div>
    </div>
@empty
    <div class="ap-card ap-card-padded text-muted">No notifications.</div>
@endforelse

{{ $notifications->links() }}
</div>
@endsection
