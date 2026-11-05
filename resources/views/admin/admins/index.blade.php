@extends('layouts.admin')

@php
    $isHq = $group === 'hq';
    $title = $isHq ? 'HQ Admins' : 'State Admins';
@endphp

@section('title', $title)

@section('content')
@include('partials.mac-styles')

<div class="mac-page">
    @include('partials.admin-alerts')

    <div class="mac-header mb-4 d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h2>{{ $title }}</h2>
            <p class="mac-muted mb-0">
                @if($isHq)
                    Head-office accounts. HQ admins run events, the draw, settings and the public site; super admins also manage accounts and zones.
                @else
                    One office zone each. A state admin sees the companies and draw lists of their own zone, and nothing else.
                @endif
            </p>
        </div>

        <a href="{{ route('admin.admins.create', ['group' => $group]) }}" class="mac-btn-primary">
            <i class="bi bi-plus-lg me-1"></i> {{ $isHq ? 'New HQ Admin' : 'New State Admin' }}
        </a>
    </div>

    <form method="GET" class="mac-filter-bar mb-3">
        <input type="search" name="search" value="{{ request('search') }}"
               class="mac-input" placeholder="Name or email" aria-label="Search admins">

        @unless($isHq)
            <select name="state" class="mac-select" aria-label="Filter by zone">
                @include('partials.zone-options', ['selected' => request('state'), 'placeholder' => 'All zones'])
            </select>
        @endunless

        <button type="submit" class="mac-filter-btn">Filter</button>

        @if(request()->hasAny(['search', 'state']))
            <a href="{{ route('admin.admins.'.$group) }}" class="mac-clear-btn">Clear</a>
        @endif
    </form>

    <div class="mac-card">
        <div class="table-responsive">
            <table class="mac-table">
                <thead>
                    <tr>
                        <th>Admin</th>
                        <th>{{ $isHq ? 'Role' : 'Zone' }}</th>
                        <th>Last seen</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($admins as $admin)
                        <tr>
                            <td>
                                <strong>{{ $admin->name }}</strong>
                                @if($admin->is($actor))
                                    <span class="mac-badge mac-badge-gray ms-1">You</span>
                                @endif
                                <div class="mac-text-sm mac-muted">{{ $admin->email }}</div>
                            </td>
                            <td class="mac-text-sm">
                                @if($isHq)
                                    <span class="mac-badge {{ $admin->isSuperAdmin() ? 'mac-badge-orange' : 'mac-badge-blue' }}">
                                        {{ $admin->isSuperAdmin() ? 'Super Admin' : 'HQ Admin' }}
                                    </span>
                                @else
                                    {{ $admin->state ?? '—' }}
                                @endif
                            </td>
                            <td class="mac-text-sm mac-muted">{{ $admin->last_seen_at?->diffForHumans() ?? 'Never' }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.admins.edit', $admin) }}" class="mac-action-btn" title="Edit" aria-label="Edit {{ $admin->name }}">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @unless($admin->is($actor))
                                    <form method="POST" action="{{ route('admin.admins.destroy', $admin) }}" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="mac-action-btn mac-action-btn-danger" title="Delete" aria-label="Delete {{ $admin->name }}"
                                                data-confirm="Delete &quot;{{ $admin->name }}&quot;? They will no longer be able to sign in.">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <div class="mac-empty">
                                    <div class="mac-empty-icon"><i class="bi bi-person-badge"></i></div>
                                    <p class="mac-empty-text">
                                        @if(request()->hasAny(['search', 'state']))
                                            No admin matches those filters.
                                        @else
                                            No {{ $isHq ? 'HQ' : 'state' }} admins yet.
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

    <div class="mt-3">{{ $admins->links() }}</div>
</div>
@endsection
