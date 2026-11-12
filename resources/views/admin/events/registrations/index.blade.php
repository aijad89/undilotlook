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
                The zone checks each company and puts it on this list; HQ verifies it.
                The company is told, and the draw can open, only once HQ has verified every row.
            </p>
            @if($waitingCount)
                <a href="{{ route('admin.events.registrations.index', [$event, 'status' => 'waiting']) }}" class="mac-badge mac-badge-orange mt-2 d-inline-block">
                    {{ $waitingCount }} waiting for HQ
                </a>
            @endif
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

    @if($joinRequests->isNotEmpty())
        <div class="mac-card mb-3" id="join-requests">
            <div class="mac-card-padded pb-0">
                <h3 class="mac-card-title">Join requests ({{ $joinRequests->count() }})</h3>
                <p class="mac-muted mac-text-sm mb-0">
                    Check the company's details, files and payment proof, set its craft code and booth, then approve with ✓
                    @unless($isHq) — HQ verifies it before the company is told —@endunless
                    or reject with a reason the company will see.
                </p>
            </div>
            <div class="table-responsive">
                <table class="mac-table">
                    <thead>
                        <tr>
                            <th>Company</th>
                            <th>Payment</th>
                            <th>Documents</th>
                            <th style="width:300px;">Approve</th>
                            <th style="width:280px;">Reject</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($joinRequests as $jr)
                            <tr>
                                <td>
                                    <strong>{{ $jr->company->name }}</strong>
                                    <div class="mac-text-sm mac-muted">{{ $jr->company->state }} · sent {{ $jr->updated_at->format('d M Y') }}</div>
                                    {{-- As the company declared it with this request. --}}
                                    @if($a = $jr->answers)
                                        <div class="mac-text-sm mt-1">
                                            @include('partials.join-answers', ['a' => $a])
                                        </div>
                                    @endif
                                </td>
                                <td class="mac-text-sm">
                                    @include('partials.payment-proof', ['jr' => $jr])
                                </td>
                                <td class="mac-text-sm">
                                    {{-- The payment files are under Payment; these are the company's own. --}}
                                    @forelse(collect($jr->documents ?? [])->reject(fn ($d) => str_starts_with($d['path'] ?? '', 'join-requests/')) as $i => $doc)
                                        <div><a href="{{ route('join-requests.document', [$jr, $i]) }}" target="_blank" rel="noopener"><i class="bi bi-file-earmark me-1"></i>{{ $doc['name'] }}</a></div>
                                    @empty
                                        <span class="mac-muted">No files</span>
                                    @endforelse
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('admin.events.registrations.approve', [$event, $jr]) }}" class="d-flex gap-1">
                                        @csrf
                                        <select name="code" class="mac-select mac-action-btn-field" aria-label="Craft code">
                                            <option value="">{{ $jr->company->kraf_code ? 'Own: '.$jr->company->kraf_code : '— None —' }}</option>
                                            @foreach($codes as $code)
                                                <option value="{{ $code->code }}">{{ $code->code }}</option>
                                            @endforeach
                                        </select>
                                        <select name="plot_size" class="mac-select mac-action-btn-field" aria-label="Booth">
                                            @foreach($plotSizes as $size)
                                                <option value="{{ $size }}" @selected(($jr->company->plot_size ?? '3x3') === $size)>{{ $size }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="mac-action-btn" title="Approve and add to the draw list">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('admin.events.registrations.reject', [$event, $jr]) }}" class="d-flex gap-1">
                                        @csrf
                                        <input type="text" name="reject_reason" class="mac-form-control mac-action-btn-field" placeholder="Reason" aria-label="Reason for rejecting" required maxlength="1000">
                                        <button type="submit" class="mac-action-btn mac-action-btn-danger" title="Reject">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

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

        <select name="status" class="mac-select" aria-label="Filter by HQ check">
            <option value="">Any check</option>
            <option value="waiting" @selected(request('status') === 'waiting')>Waiting for HQ</option>
            <option value="returned" @selected(request('status') === 'returned')>Returned by HQ</option>
            <option value="verified" @selected(request('status') === 'verified')>Verified</option>
        </select>

        <button type="submit" class="mac-filter-btn">Filter</button>

        @if(request()->hasAny(['search', 'code', 'status']))
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
                            <th>Payment</th>
                            <th>HQ check</th>
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

                                <td class="mac-text-sm">
                                    @if(isset($proofs[$registration->company_id]))
                                        @include('partials.payment-proof', ['jr' => $proofs[$registration->company_id]])
                                    @else
                                        {{-- Added by the office: no request, so the zone writes how it paid. --}}
                                        <form method="POST" action="{{ route('admin.events.registrations.payment-note', [$event, $registration]) }}" class="d-flex gap-1">
                                            @csrf
                                            @method('PUT')
                                            <input type="text" name="payment_note" value="{{ $registration->payment_note }}" maxlength="500"
                                                   class="mac-form-control mac-action-btn-field" placeholder="Receipt no., date, how paid"
                                                   aria-label="Payment note for {{ $registration->company->name }}">
                                            <button type="submit" class="mac-action-btn" title="Save payment note"><i class="bi bi-check-lg"></i></button>
                                        </form>
                                        @unless($registration->payment_note)
                                            <div class="mac-muted mt-1">Added by the office, no payment note yet.</div>
                                        @endunless
                                    @endif
                                </td>

                                <td class="mac-text-sm">
                                    @if($registration->verified_at)
                                        <span class="mac-badge mac-badge-green">Verified</span>
                                    @elseif($registration->hq_note)
                                        <span class="mac-badge mac-badge-red">Returned by HQ</span>
                                        <div class="mt-1">{{ $registration->hq_note }}</div>
                                    @else
                                        <span class="mac-badge mac-badge-orange">Waiting for HQ</span>
                                        @if($registration->verified_by)
                                            <div class="mac-muted mt-1">Changed since HQ verified it.</div>
                                        @endif
                                    @endif
                                </td>

                                {{-- One row, one small form. The code and size are the
                                     two things the plan is built from, so they are
                                     edited where they are read. From HQ, the button
                                     is the verification: it saves and verifies. --}}
                                @if($codeFrozen)
                                <td>{{ $registration->code ?? '—' }}</td>
                                <td>{{ $registration->plot_size }}</td>
                                @else
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

                                        @if($isHq)
                                            <button type="submit" class="mac-action-btn" title="Verify (saves any change)">
                                                <i class="bi bi-check2-all"></i>
                                            </button>
                                        @else
                                            <button type="submit" class="mac-action-btn" title="Save — HQ verifies it again">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                        @endif
                                    </form>
                                </td>
                                @endif

                                <td>
                                    @if($registration->plot)
                                        <span class="mac-badge mac-badge-green text-nowrap">{{ $registration->plot->plot_code }}</span>
                                    @else
                                        <span class="mac-muted mac-text-sm">—</span>
                                    @endif
                                </td>

                                <td class="text-end">
                                    @if($isHq)
                                        <details class="d-inline-block text-start mb-1">
                                            <summary class="mac-text-sm">Return</summary>
                                            <form method="POST" action="{{ route('admin.events.registrations.return', [$event, $registration]) }}" class="d-flex gap-1 mt-1">
                                                @csrf
                                                <input type="text" name="hq_note" class="mac-form-control mac-action-btn-field" placeholder="What to fix" aria-label="What the zone should fix" required maxlength="1000">
                                                <button type="submit" class="mac-action-btn mac-action-btn-danger" title="Return to the zone">
                                                    <i class="bi bi-arrow-return-left"></i>
                                                </button>
                                            </form>
                                        </details>
                                    @endif
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
                                <td colspan="9">
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
                            @if($isHq)
                            <option value="verify">Verify</option>
                            @endif
                            @unless($codeFrozen)
                            <option value="set_code">Set craft code</option>
                            <option value="set_size">Set booth size</option>
                            @endunless
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
