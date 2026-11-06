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

        <div class="d-flex flex-wrap gap-2">
            {{-- Downloads the rows this table is showing: same search, zone and "missing" filter. --}}
            <a href="{{ route('admin.reports.companies', request()->only('search', 'state', 'missing')) }}" class="mac-btn-secondary">
                <i class="bi bi-download me-1"></i> Export
            </a>
            <a href="{{ route('admin.companies.create') }}" class="mac-btn-primary">
                <i class="bi bi-plus-lg me-1"></i> New Company
            </a>
        </div>
    </div>

    <form method="GET" class="mac-filter-bar mb-3">
        <input type="search" name="search" value="{{ request('search') }}"
               class="mac-input" placeholder="Company, owner, email, IC or community ID" aria-label="Search companies">

        @if($canFilterZone)
            <select name="state" class="mac-select" aria-label="Filter by zone">
                @include('partials.zone-options', ['selected' => request('state'), 'placeholder' => 'All zones'])
            </select>
        @endif

        @if(in_array(request('missing'), ['category', 'code'], true))
            <input type="hidden" name="missing" value="{{ request('missing') }}">
            <span class="mac-badge mac-badge-orange">
                {{ request('missing') === 'category' ? 'No craft category' : 'No craft code' }}
            </span>
        @endif

        <button type="submit" class="mac-filter-btn">Filter</button>

        @if(request()->hasAny(['search', 'state', 'missing']))
            <a href="{{ route('admin.companies.index') }}" class="mac-clear-btn">Clear</a>
        @endif
    </form>

    <div class="mac-card">
        <div class="table-responsive">
            <table class="mac-table">
                <thead>
                    <tr>
                        <th class="text-end">No.</th>
                        <th>Name</th>
                        <th>IC</th>
                        <th>Community ID</th>
                        <th>Company</th>
                        <th>Craft category</th>
                        <th>Sub-category</th>
                        <th>Zone</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($companies as $company)
                        <tr>
                            <td class="text-end mac-muted">{{ $companies->firstItem() + $loop->index }}</td>
                            <td>{{ $company->user->name }}</td>
                            <td class="text-nowrap">{{ $company->user->ic_number ?? '—' }}</td>
                            <td class="text-nowrap">{{ $company->user->community_id ?? '—' }}</td>
                            <td>
                                <strong>{{ $company->name }}</strong>
                                @unless($company->is_active)
                                    <div><span class="mac-badge mac-badge-gray">Switched off</span></div>
                                @endunless
                            </td>
                            {{-- One line per selection, so each sub-category sits beside its category. --}}
                            <td class="mac-text-sm">
                                @forelse($company->krafSelections as $selection)
                                    <div>{{ $selection->bidang?->name }}</div>
                                @empty
                                    —
                                @endforelse
                            </td>
                            <td class="mac-text-sm">
                                @forelse($company->krafSelections as $selection)
                                    <div>{{ $selection->subBidang?->name }}</div>
                                @empty
                                    —
                                @endforelse
                            </td>
                            <td class="mac-text-sm">{{ $company->state ?? '—' }}</td>
                            <td class="text-end text-nowrap">
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
                            <td colspan="9">
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
