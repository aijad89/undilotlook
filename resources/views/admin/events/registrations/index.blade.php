@extends('layouts.admin')

@section('title', 'Draw List')

@section('content')
@include('partials.mac-styles')

<div class="mac-page">
    @include('partials.admin-alerts')

    <div class="mac-breadcrumb mb-2">
        <a href="{{ route('admin.events.index') }}">Events</a> <span class="sep" aria-hidden="true">›</span> {{ $event->title }}
    </div>

    <div class="mac-header mb-4 d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h2>Draw List</h2>
            <p class="mac-muted mb-0">
                {{ $event->title }} — {{ $event->start_date->format('d M Y') }}.
                Being on this list is being in the draw; there is nothing further to approve.
            </p>
        </div>

        @if($undoCount)
            <form method="POST" action="{{ route('admin.events.registrations.undo', $event) }}">
                @csrf
                <button type="submit" class="mac-btn-secondary">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>
                    Undo last removal ({{ $undoCount }})
                </button>
            </form>
        @endif
    </div>

    <div class="mac-card mac-card-padded mb-3">
        <h3 class="mac-card-title">Add companies</h3>

        @if($candidates->isEmpty())
            <p class="mac-muted mb-0">Every company in your zone is already in this draw.</p>
        @else
            <form method="POST" action="{{ route('admin.events.registrations.store', $event) }}">
                @csrf
                <div class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label class="mac-label" for="company_id">Companies</label>
                        <select id="company_id" name="company_id[]" class="mac-select" multiple size="6" required>
                            @foreach($candidates as $candidate)
                                <option value="{{ $candidate->id }}">
                                    {{ $candidate->name }}@if($candidate->state) — {{ $candidate->state }}@endif @if($candidate->kraf_code || $candidate->plot_size) [{{ collect([$candidate->kraf_code, $candidate->plot_size])->filter()->join(' · ') }}]@endif
                                </option>
                            @endforeach
                        </select>
                        <small class="mac-muted">Ctrl or Shift to pick several.</small>
                    </div>

                    <div class="col-md-3">
                        <label class="mac-label" for="code">Craft code</label>
                        <select id="code" name="code" class="mac-select">
                            {{-- Blank: each company gets the code on its own page, shown in [ ] above. --}}
                            <option value="">— Each company's own code —</option>
                            @foreach($codes as $code)
                                <option value="{{ $code->code }}">{{ $code->code }} — {{ $code->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="mac-label" for="plot_size">Booth size</label>
                        <select id="plot_size" name="plot_size" class="mac-select">
                            {{-- Blank: each company gets the size on its own page, else 3x3. --}}
                            <option value="">— Each company's own size —</option>
                            @foreach($plotSizes as $size)
                                <option value="{{ $size }}">{{ $size }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-1">
                        <button type="submit" class="mac-btn-primary w-100">Add</button>
                    </div>
                </div>
            </form>
        @endif
    </div>

    <form method="GET" class="mac-filter-bar mb-3">
        <input type="search" name="search" value="{{ request('search') }}"
               class="mac-input" placeholder="Company name" aria-label="Search the draw list">

        <select name="code" class="mac-select" aria-label="Filter by craft code">
            <option value="">All codes</option>
            @foreach($codes as $code)
                <option value="{{ $code->code }}" @selected(request('code') === $code->code)>{{ $code->code }}</option>
            @endforeach
        </select>

        <button type="submit" class="mac-filter-btn">Filter</button>

        @if(request()->hasAny(['search', 'code']))
            <a href="{{ route('admin.events.registrations.index', $event) }}" class="mac-clear-btn">Clear</a>
        @endif
    </form>

    <div class="mac-card">
            <div class="table-responsive">
                <table class="mac-table">
                    <thead>
                        <tr>
                            <th style="width:32px;">
                                <input type="checkbox" id="tick-all" aria-label="Select every row">
                            </th>
                            <th>Company</th>
                            <th>Account</th>
                            <th style="width:220px;">Craft code</th>
                            <th style="width:120px;">Booth</th>
                            <th>Lot</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($registrations as $registration)
                            <tr>
                                <td data-label="Select">
                                    {{-- Associated with the bulk form BELOW the table by id.
                                         The per-row forms sit inside this table, and a form
                                         inside a form is not a thing a browser will send. --}}
                                    <input type="checkbox" name="registration_id[]" value="{{ $registration->id }}"
                                           class="tick-row" form="bulk-form"
                                           aria-label="Select {{ $registration->company->name }}">
                                </td>
                                <td>
                                    <strong>{{ $registration->company->name }}</strong>
                                    <div class="mac-text-sm mac-muted">{{ $registration->company->state }}</div>
                                </td>
                                <td class="mac-text-sm">
                                    @if($registration->user_id)
                                        {{ $registration->company->user->email }}
                                    @else
                                        <span class="mac-badge mac-badge-orange">No account</span>
                                    @endif
                                </td>

                                {{-- One row, one small form. The code and size are the
                                     two things the plan is built from, so they are
                                     edited where they are read. --}}
                                <td colspan="2">
                                    <form method="POST"
                                          action="{{ route('admin.events.registrations.update', [$event, $registration]) }}"
                                          class="d-flex gap-1">
                                        @csrf
                                        @method('PUT')
                                        <select name="code" class="mac-select mac-action-btn-field" aria-label="Craft code">
                                            <option value="">— None —</option>
                                            @foreach($codes as $code)
                                                <option value="{{ $code->code }}" @selected($registration->code === $code->code)>
                                                    {{ $code->code }}
                                                </option>
                                            @endforeach
                                            @if($registration->code && ! $codes->contains('code', $registration->code))
                                                {{-- A code withdrawn since it was set. Still shown,
                                                     because the row carries it and hiding it would
                                                     silently blank it on the next save. --}}
                                                <option value="{{ $registration->code }}" selected>
                                                    {{ $registration->code }} (retired)
                                                </option>
                                            @endif
                                        </select>

                                        <select name="plot_size" class="mac-select mac-action-btn-field" aria-label="Booth">
                                            @foreach($plotSizes as $size)
                                                <option value="{{ $size }}" @selected($registration->plot_size === $size)>
                                                    {{ $size }}
                                                </option>
                                            @endforeach
                                            @if($registration->plot_size && ! in_array($registration->plot_size, $plotSizes, true))
                                                <option value="{{ $registration->plot_size }}" selected>
                                                    {{ $registration->plot_size }}
                                                </option>
                                            @endif
                                        </select>

                                        <button type="submit" class="mac-action-btn" title="Save this row">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                    </form>
                                </td>

                                <td>
                                    @if($registration->plot_id)
                                        <span class="mac-badge mac-badge-green">Holds a lot</span>
                                    @else
                                        <span class="mac-muted mac-text-sm">—</span>
                                    @endif
                                </td>

                                <td class="text-end">
                                    <form method="POST"
                                          action="{{ route('admin.events.registrations.destroy', [$event, $registration]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="mac-action-btn mac-action-btn-danger"
                                                title="Remove from the draw list"
                                                data-confirm="Remove {{ $registration->company->name }} from this draw?">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="mac-empty">
                                        <div class="mac-empty-icon"><i class="bi bi-list-check"></i></div>
                                        <p class="mac-empty-text">Nobody is in this draw yet.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
    </div>

    @if($registrations->isNotEmpty())
        <form method="POST" action="{{ route('admin.events.registrations.bulk', $event) }}"
              id="bulk-form" class="mac-card mac-card-padded mt-3">
            @csrf
            <h3 class="mac-card-title">With the ticked rows</h3>

                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="mac-label" for="bulk-action">Action</label>
                        <select id="bulk-action" name="action" class="mac-select" required>
                            <option value="set_code">Set craft code</option>
                            <option value="set_size">Set booth size</option>
                            <option value="remove">Remove from the draw</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="mac-label" for="bulk-code">Craft code</label>
                        <select id="bulk-code" name="code" class="mac-select">
                            <option value="">— None —</option>
                            @foreach($codes as $code)
                                <option value="{{ $code->code }}">{{ $code->code }} — {{ $code->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="mac-label" for="bulk-size">Booth size</label>
                        <select id="bulk-size" name="plot_size" class="mac-select">
                            @foreach($plotSizes as $size)
                                <option value="{{ $size }}">{{ $size }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <button type="submit" class="mac-btn-primary w-100"
                                data-confirm="Apply this to every ticked row?">
                            Apply
                        </button>
                    </div>
                </div>

            <p class="mac-muted mac-text-sm mt-2 mb-0">
                A row holding a lot is kept, whatever is ticked. Withdraw the lot first.
            </p>
        </form>
    @endif

    <div class="mt-3">{{ $registrations->links() }}</div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const all = document.getElementById('tick-all');

    if (all) {
        all.addEventListener('change', function () {
            document.querySelectorAll('.tick-row').forEach(box => { box.checked = all.checked; });
        });
    }
});
</script>
@endpush
@endsection
