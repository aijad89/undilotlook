@extends('layouts.admin')

@section('title', 'Events')

@section('content')
@include('partials.mac-styles')

<div class="mac-page">
    @include('partials.admin-alerts')

    <div class="mac-header mb-4 d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h2>Events</h2>
            <p class="mac-muted mb-0">Every event, and how many companies are in each draw list.</p>
        </div>

        @if($canManage)
            <a href="{{ route('admin.events.create') }}" class="mac-btn-primary">
                <i class="bi bi-plus-lg me-1"></i> New Event
            </a>
        @endif
    </div>

    <form method="GET" class="mac-filter-bar mb-3">
        <input type="search" name="search" value="{{ request('search') }}"
               class="mac-input" placeholder="Title or location" aria-label="Search events">

        <select name="status" class="mac-select" aria-label="Filter by status">
            <option value="">All statuses</option>
            @foreach(\App\Models\Event::STATUSES as $value => $label)
                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
            @endforeach
        </select>

        <select name="state" class="mac-select" aria-label="Filter by zone">
            @include('partials.zone-options', ['selected' => request('state'), 'placeholder' => 'All zones'])
        </select>

        <button type="submit" class="mac-filter-btn">Filter</button>

        @if(request()->hasAny(['search', 'status', 'state']))
            <a href="{{ route('admin.events.index') }}" class="mac-clear-btn">Clear</a>
        @endif
    </form>

    <div class="mac-card">
        <div class="table-responsive">
            <table class="mac-table">
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>When</th>
                        <th>Zone</th>
                        <th>Status</th>
                        <th class="text-end">In the draw</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($events as $event)
                        <tr>
                            <td>
                                <strong>{{ $event->title }}</strong>
                                <div class="mac-text-sm mac-muted">{{ $event->location }}</div>
                            </td>
                            <td class="mac-text-sm">
                                {{ $event->start_date->format('d M Y') }}
                                @if(! $event->start_date->isSameDay($event->end_date))
                                    – {{ $event->end_date->format('d M Y') }}
                                @endif
                            </td>
                            <td class="mac-text-sm">{{ $event->state ?? '—' }}</td>
                            <td>
                                <span class="mac-status {{ $event->status_colour }}">
                                    <span class="mac-status-dot"></span>{{ $event->status_label }}
                                </span>
                                <div class="mac-text-sm mac-muted mt-1">
                                    Draw: {{ $event->polling_status_label }}
                                </div>
                            </td>
                            <td class="text-end">{{ $event->registrations_count }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.events.registrations.index', $event) }}"
                                   class="mac-action-btn" title="Draw list">
                                    <i class="bi bi-list-check"></i>
                                </a>

                                @if($canManage)
                                    <a href="{{ route('admin.events.edit', $event) }}"
                                       class="mac-action-btn" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                    <form method="POST" action="{{ route('admin.events.destroy', $event) }}"
                                          class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        {{-- The value travels as data, never through a JavaScript parser.
                                             The layout reads data-confirm with dataset. --}}
                                        <button type="submit" class="mac-action-btn mac-action-btn-danger"
                                                title="Delete"
                                                data-confirm="Delete &quot;{{ $event->title }}&quot;? Everything in its draw goes with it.">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="mac-empty">
                                    <div class="mac-empty-icon"><i class="bi bi-calendar-event"></i></div>
                                    <p class="mac-empty-text">
                                        @if(request()->hasAny(['search', 'status', 'state']))
                                            No event matches those filters.
                                        @else
                                            No events yet.
                                        @endif
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $events->links() }}</div>
</div>
@endsection
