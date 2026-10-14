@extends('layouts.admin')

@section('title', 'Audit Trail')

@section('content')
@include('partials.mac-styles')
<div class="mac-page">
    <div class="mac-header d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>Audit Trail</h2>
            <div class="mac-breadcrumb">
                <a href="{{ route('admin.dashboard') }}">Dashboard</a>
                <span class="sep" aria-hidden="true">›</span> Audit Trail
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="mac-filter-bar mb-4">
        <form method="GET" action="{{ route('admin.audit-trails.index') }}" class="row g-3 align-items-end">
            <div class="col-md-2">
                <label class="mac-label" for="at-action">Action</label>
                <select name="action" id="at-action" class="mac-select">
                    <option value="">All</option>
                    @foreach($actions as $action)
                        <option value="{{ $action }}" {{ request('action') == $action ? 'selected' : '' }}>
                            {{ ucfirst($action) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="mac-label" for="at-model">Model</label>
                <select name="model" id="at-model" class="mac-select">
                    <option value="">All</option>
                    @foreach($models as $model)
                        <option value="{{ $model }}" {{ request('model') == $model ? 'selected' : '' }}>
                            {{ $model }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="mac-label" for="at-from">From</label>
                <input type="date" name="from" id="at-from" class="mac-input" value="{{ request('from') }}">
            </div>
            <div class="col-md-2">
                <label class="mac-label" for="at-to">To</label>
                <input type="date" name="to" id="at-to" class="mac-input" value="{{ request('to') }}">
            </div>
            <div class="col-md-2">
                <label class="mac-label" for="at-search">Search (IP/URL)</label>
                <input type="text" name="search" id="at-search" class="mac-input" value="{{ request('search') }}" placeholder="Search...">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="mac-filter-btn w-100">Filter</button>
                <a href="{{ route('admin.audit-trails.index') }}" class="mac-clear-btn">Clear</a>
            </div>
        </form>
    </div>

    <div class="mac-card">
        <div class="table-responsive">
            <table class="mac-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Performer</th>
                        <th>Action</th>
                        <th>Model</th>
                        <th>ID</th>
                        <th>IP Address</th>
                        <th>Date</th>
                        <th class="text-end">Details</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($audits as $audit)
                        @php
                            [$badgeClass, $badgeLabel] = $audit->action_badge;
                            $macStatus = match(true) {
                                str_contains($badgeClass, 'success') => 'mac-status-green',
                                str_contains($badgeClass, 'info') => 'mac-status-blue',
                                str_contains($badgeClass, 'warning') => 'mac-status-orange',
                                str_contains($badgeClass, 'danger') => 'mac-status-red',
                                default => 'mac-status-gray',
                            };
                        @endphp
                        <tr>
                            <td>{{ $loop->iteration + ($audits->currentPage() - 1) * $audits->perPage() }}</td>
                            <td>
                                @if($audit->performer)
                                    <span class="mac-status mac-status-gray"><span class="mac-status-dot"></span>{{ class_basename($audit->performer_type) }}</span>
                                    {{ $audit->performer->name ?? $audit->performer->email ?? '—' }}
                                @else
                                    <span class="mac-muted">System</span>
                                @endif
                            </td>
                            <td><span class="mac-status {{ $macStatus }}"><span class="mac-status-dot"></span>{{ $badgeLabel }}</span></td>
                            <td>{{ $audit->model_label }}</td>
                            <td><code>{{ $audit->auditable_id ?? '—' }}</code></td>
                            <td class="mac-muted">{{ $audit->ip_address ?? '—' }}</td>
                            <td class="mac-muted">{{ $audit->created_at->format('d M Y, H:i') }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.audit-trails.show', $audit) }}" class="mac-action-btn">
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="mac-empty">
                                    <div class="mac-empty-icon">📋</div>
                                    <div class="mac-empty-text">No audit records found.</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $audits->links() }}
    </div>
</div>
@endsection
