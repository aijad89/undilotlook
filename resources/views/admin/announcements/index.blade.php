@extends('layouts.admin')

@section('title', 'Announcements')

@section('content')
@include('partials.mac-styles')

<div class="mac-page">
    @include('partials.admin-alerts')

    <div class="mac-header mb-4 d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h2>Announcements</h2>
            <p class="mac-muted mb-0">What has been sent, and to whom.</p>
        </div>
        @if($canSend)
            <a href="{{ route('admin.announcements.create') }}" class="mac-btn-primary">
                <i class="bi bi-plus-lg me-1"></i> New Announcement
            </a>
        @endif
    </div>

    <form method="GET" class="mac-filter-bar mb-3 d-flex gap-2">
        <select name="type" class="mac-select" style="max-width:220px" aria-label="Filter by type">
            <option value="">Every type</option>
            @foreach(\App\Models\Announcement::TYPES as $key => $label)
                <option value="{{ $key }}" @selected(request('type') === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <button type="submit" class="mac-filter-btn">Filter</button>
    </form>

    <div class="mac-card">
        <div class="table-responsive">
            <table class="mac-table">
                <thead>
                    <tr><th>Title</th><th>Type</th><th>Sent to</th><th>Files</th><th>Sent</th></tr>
                </thead>
                <tbody>
                    @forelse($announcements as $announcement)
                        <tr>
                            <td><a href="{{ route('admin.announcements.show', $announcement) }}" class="fw-semibold">{{ $announcement->title }}</a></td>
                            <td>{{ \App\Models\Announcement::TYPES[$announcement->type] ?? $announcement->type }}</td>
                            <td>{{ $announcement->sentToLabel() }}</td>
                            <td>
                                @if($announcement->banner) <i class="bi bi-image" title="Banner"></i> @endif
                                @if($announcement->attachment) <i class="bi bi-paperclip" title="Attachment"></i> @endif
                            </td>
                            <td>{{ $announcement->created_at->format('d M Y, H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><div class="mac-empty">No announcements yet.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $announcements->links() }}
    </div>
</div>
@endsection
