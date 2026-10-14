@extends('layouts.admin')

@section('title', 'Companies')

@section('content')
@include('partials.mac-styles')

<div class="mac-page">
    @include('partials.admin-alerts')

    <div class="mac-header mb-4 d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h2>Companies</h2>
            <p class="mac-muted mb-0">One company, one account. Both are made on the same form.</p>
        </div>

        <a href="{{ route('admin.companies.create') }}" class="mac-btn-primary">
            <i class="bi bi-plus-lg me-1"></i> New Company
        </a>
    </div>

    <form method="GET" class="mac-filter-bar mb-3">
        <input type="search" name="search" value="{{ request('search') }}"
               class="mac-input" placeholder="Company, owner or email" aria-label="Search companies">

        @if($canFilterZone)
            <select name="state" class="mac-select" aria-label="Filter by zone">
                @include('partials.zone-options', ['selected' => request('state'), 'placeholder' => 'All zones'])
            </select>
        @endif

        <button type="submit" class="mac-filter-btn">Filter</button>

        @if(request()->hasAny(['search', 'state']))
            <a href="{{ route('admin.companies.index') }}" class="mac-clear-btn">Clear</a>
        @endif
    </form>

    <div class="mac-card">
        <div class="table-responsive">
            <table class="mac-table">
                <thead>
                    <tr>
                        <th>Company</th>
                        <th>Account</th>
                        <th>Zone</th>
                        <th>Status</th>
                        <th class="text-end">Draws</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($companies as $company)
                        <tr>
                            <td>
                                <strong>{{ $company->name }}</strong>
                                @if($company->entrepreneur_category)
                                    <div class="mac-text-sm mac-muted">{{ $company->entrepreneur_category }}</div>
                                @endif
                            </td>
                            <td class="mac-text-sm">
                                {{ $company->user->name }}
                                <div class="mac-muted">{{ $company->user->email }}</div>
                            </td>
                            <td class="mac-text-sm">{{ $company->state ?? '—' }}</td>
                            <td>
                                <span class="mac-badge {{ $company->is_active ? 'mac-badge-green' : 'mac-badge-gray' }}">
                                    {{ $company->is_active ? 'On the books' : 'Switched off' }}
                                </span>
                                @unless($company->user->is_active)
                                    <div class="mac-text-sm mac-muted mt-1">Account cannot sign in</div>
                                @endunless
                            </td>
                            <td class="text-end">{{ $company->event_registrations_count }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.companies.edit', $company) }}"
                                   class="mac-action-btn" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>

                                <form method="POST" action="{{ route('admin.companies.destroy', $company) }}"
                                      class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="mac-action-btn mac-action-btn-danger" title="Delete"
                                            data-confirm="Delete &quot;{{ $company->name }}&quot; and its account?">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="mac-empty">
                                    <div class="mac-empty-icon"><i class="bi bi-building"></i></div>
                                    <p class="mac-empty-text">
                                        @if(request()->hasAny(['search', 'state']))
                                            No company matches those filters.
                                        @else
                                            No companies yet.
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

    <div class="mt-3">{{ $companies->links() }}</div>
</div>
@endsection
