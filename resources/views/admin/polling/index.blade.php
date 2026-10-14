@extends('layouts.admin')

@section('title', 'Lot Draw')

@section('content')
@include('partials.mac-styles')

<div class="mac-page">
    @include('partials.admin-alerts')

    <div class="mac-header mb-4 d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h2>Lot Draw</h2>
            <p class="mac-muted mb-0">Every event with a lot plan, and the stage its draw is at.</p>
        </div>

        @if($canManage)
            <a href="{{ route('admin.polling.create') }}" class="mac-btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Create Lot Draw
            </a>
        @endif
    </div>

    <form method="GET" class="mac-filter-bar mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-5">
                <input type="search" name="search" class="mac-input" aria-label="Search by event title"
                       placeholder="Search by event title…" value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="polling_status" class="mac-select" aria-label="Filter by draw stage">
                    <option value="">Every stage</option>
                    @foreach(\App\Models\Event::POLLING_STATUSES as $key => $label)
                        <option value="{{ $key }}" @selected(request('polling_status') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="mac-filter-btn">Search</button>
                <a href="{{ route('admin.polling.index') }}" class="mac-clear-btn">Clear</a>
            </div>
        </div>
    </form>

    <div class="mac-card">
        <div class="table-responsive">
            <table class="mac-table">
                <thead>
                    <tr>
                        <th>Event</th>
                        <th class="text-center">Plots</th>
                        <th class="text-center">Locations</th>
                        <th class="text-center">Code allocations</th>
                        <th class="text-center">Stage</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($events as $event)
                        @php
                            // The useful date on this list is when voting runs, not
                            // when the fair opens — they are often weeks apart.
                            $ps = $event->polling_start_date;
                            $pd = $event->polling_deadline;
                            $when = match (true) {
                                $ps && $pd && $ps->isSameDay($pd) => $ps->format('d M Y'),
                                $ps && $pd => $ps->format('d M').' – '.$pd->format('d M Y'),
                                default => null,
                            };
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('admin.polling.show', $event) }}" class="fw-semibold text-decoration-none">
                                    {{ $event->title }}
                                </a>
                                <div class="mac-muted small" title="Event date: {{ $event->start_date?->format('d M Y') }}">
                                    @if($when)
                                        <i class="bi bi-calendar-check" aria-hidden="true"></i> Voting {{ $when }}
                                    @else
                                        No voting window set
                                    @endif
                                </div>
                            </td>
                            <td class="text-center">{{ $event->total_plots ?? 0 }}</td>
                            <td class="text-center">{{ $event->plotSizes->pluck('location')->unique()->count() }}</td>
                            <td class="text-center">{{ $event->bidangAllocations->count() }}</td>
                            <td class="text-center">
                                <span class="mac-status {{ $event->polling_status_colour }}">
                                    <span class="mac-status-dot"></span>{{ $event->polling_status_label }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.polling.show', $event) }}" class="mac-action-btn">View</a>

                                @if($canManage && in_array($event->polling_status, ['draft', 'cancelled'], true))
                                    @if($event->has_assigned_plots)
                                        <span class="mac-muted small ms-1" title="Lots are already held in this draw">Plan locked</span>
                                    @else
                                        <a href="{{ route('admin.polling.edit', $event) }}" class="mac-action-btn">Edit</a>
                                        <form action="{{ route('admin.polling.destroy', $event) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="mac-action-btn-danger"
                                                    data-confirm="Clear the lot plan for {{ $event->title }}? Every plot, location and code allocation goes, and the voting window is reset.">
                                                Clear
                                            </button>
                                        </form>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="mac-empty">
                                    <div class="mac-empty-text">
                                        {{ request()->hasAny(['search', 'polling_status']) ? 'No lot draw matches that search.' : 'No event has a lot plan yet.' }}
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $events->links() }}
    </div>
</div>
@endsection
