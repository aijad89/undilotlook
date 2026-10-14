@extends('layouts.admin')

@section('title', $announcement->title)

@section('content')
@include('partials.mac-styles')

<div class="mac-page">
    <div class="mac-breadcrumb mb-2">
        <a href="{{ route('admin.announcements.index') }}">Announcements</a> <span class="sep" aria-hidden="true">›</span> {{ $announcement->title }}
    </div>

    <div class="mac-header mb-4">
        <h2>{{ $announcement->title }}</h2>
        <p class="mac-muted mb-0">
            {{ \App\Models\Announcement::TYPES[$announcement->type] ?? $announcement->type }}
            · {{ $announcement->sentToLabel() }} · {{ $announcement->created_at->format('d M Y, H:i') }}
        </p>
    </div>

    <div class="row g-3 mb-4">
        @foreach([['Delivered', $delivered], ['Read', $read], ['Still queued', $queued]] as [$label, $value])
            <div class="col-4">
                <div class="mac-card mac-card-padded">
                    <div class="mac-label mb-1">{{ $label }}</div>
                    <div class="fs-3 fw-bold">{{ $value }}</div>
                </div>
            </div>
        @endforeach
    </div>

    @if($queued > 0)
        <div class="alert alert-info">
            Announcements are queued. They reach inboxes when the queue worker runs —
            on the server that is <code>undilot-queue.service</code>.
        </div>
    @endif

    <div class="mac-card mac-card-padded">
        @if($announcement->banner)
            <img src="{{ route('announcements.file', [$announcement, 'banner']) }}" alt=""
                 style="max-width:100%;border-radius:8px;margin-bottom:16px;">
        @endif
        <div style="white-space:pre-line;">{{ $announcement->message }}</div>
        @if($announcement->attachment)
            <a href="{{ route('announcements.file', [$announcement, 'attachment']) }}" class="mac-btn-secondary mt-3" target="_blank" rel="noopener">
                <i class="bi bi-paperclip"></i> Attachment
            </a>
        @endif
    </div>
</div>
@endsection
