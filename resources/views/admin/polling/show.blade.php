@extends('layouts.admin')

@section('title', 'Lot Draw — '.$event->title)

@section('content')
@include('partials.mac-styles')

@php
    $stage = $event->polling_status;
    $held = $event->plots->where('is_allocated', true)->count();
    $seated = $event->registrations->whereNotNull('plot_id')->count();
    $planLocked = $event->plots->contains('is_allocated', true);

    // Read at the moment of the decision, before Start is pressed: who will
    // draw outside their own craft because their code has no plot.
    $gapPrompt = $codeGaps->isEmpty()
        ? 'Open voting? Companies in the draw can confirm their lot from now on.'
        : "Open voting?\n\n{$codeGaps->sum()} registration(s) hold a code with no plot of its own — "
          .$codeGaps->map(fn ($n, $code) => "{$code} ({$n})")->implode(', ')
          .".\nThey will draw from the uncoded plots instead, and the plan locks as soon as the first lot is drawn.";
@endphp

<div class="mac-page">
    @include('partials.admin-alerts')

    <div class="mac-breadcrumb mb-2">
        <a href="{{ route('admin.polling.index') }}">Lot Draw</a> <span class="sep" aria-hidden="true">›</span> {{ $event->title }}
    </div>

    <div class="mac-header mb-4 d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h2>{{ $event->title }}</h2>
            @if($event->polling_start_date && $event->polling_deadline)
                <p class="mac-muted mb-0">
                    <i class="bi bi-calendar-range me-1" aria-hidden="true"></i>
                    Voting {{ $event->polling_start_date->format('d M Y, H:i') }} – {{ $event->polling_deadline->format('d M Y, H:i') }}
                </p>
            @endif
        </div>

        @if($canManage)
            {{-- One form per transition. changeStatus() holds the map and the
                 guards; these only offer what the map allows from here. --}}
            @php
                $moves = match ($stage) {
                    'draft' => [['polling', 'Open voting', 'mac-btn-success', $gapPrompt], ['cancelled', 'Cancel draw', 'mac-btn-secondary', 'Cancel this draw? It can be reset to draft later.']],
                    'polling' => [['closed', 'Close voting', 'mac-btn-danger', 'Close voting? No more lots can be confirmed.'], ['cancelled', 'Cancel draw', 'mac-btn-secondary', 'Cancel this draw?']],
                    'closed' => [['polling', 'Reopen voting', 'mac-btn-secondary', 'Reopen voting? Companies without a lot can confirm again.'], ['completed', 'Mark completed', 'mac-btn-success', 'Mark this draw completed? There is no way back from completed.'], ['cancelled', 'Cancel draw', 'mac-btn-secondary', 'Cancel this draw?']],
                    'cancelled' => [['draft', 'Reset to draft', 'mac-btn-secondary', 'Reset this draw to draft?']],
                    default => [],
                };
            @endphp
            <div class="d-flex flex-wrap gap-2">
                @if($stage === 'closed')
                    <form method="POST" action="{{ route('admin.polling.process', $event) }}">
                        @csrf
                        <button type="submit" class="mac-btn-primary"
                                data-confirm="Draw a lot for every company still without one? Anyone who cannot be placed is named afterwards.">
                            Assign remaining lots
                        </button>
                    </form>
                @endif
                @foreach($moves as [$to, $label, $class, $confirm])
                    <form method="POST" action="{{ route('admin.polling.change-status', $event) }}">
                        @csrf
                        <input type="hidden" name="polling_status" value="{{ $to }}">
                        <button type="submit" class="{{ $class }}" data-confirm="{{ $confirm }}"
                                @disabled($to === 'polling' && $stage === 'draft' && $blocker)>
                            {{ $label }}
                        </button>
                    </form>
                @endforeach
            </div>
        @endif
    </div>

    @if($blocker)
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle"></i> {{ $blocker }}
        </div>
    @endif

    {{-- Where the draw is, and how full it is, in one band. --}}
    <div class="mac-card draw-band mb-4">
        <div class="draw-band-stage">
            @include('partials.draw-stepper', ['status' => $stage])
        </div>
        <dl class="draw-band-facts">
            @foreach([
                ['Plots', $event->plots->count()],
                ['Held', $held],
                ['In the draw', $event->registrations->count()],
                ['Seated', $seated],
            ] as [$label, $value])
                <div>
                    <dt>{{ $label }}</dt>
                    <dd class="ui-num">{{ $value }}</dd>
                </div>
            @endforeach
        </dl>
    </div>

    <ul class="nav nav-tabs mb-3" role="tablist">
        @foreach(['plan' => 'Plan', 'draw' => 'Draw list', 'plots' => 'Plots'] + ($canManage ? ['setup' => 'Setup'] : []) as $id => $label)
            <li class="nav-item" role="presentation">
                <button class="nav-link @if($loop->first) active @endif" data-bs-toggle="tab"
                        data-bs-target="#tab-{{ $id }}" type="button" role="tab">{{ $label }}</button>
            </li>
        @endforeach
    </ul>

    <div class="tab-content">
        {{-- ── Plan ─────────────────────────────────────────────────────── --}}
        <div class="tab-pane fade show active" id="tab-plan" role="tabpanel">
            <div class="mac-card mb-3">
                <div class="mac-card-header d-flex justify-content-between align-items-center">
                    <div class="mac-card-title">Locations</div>
                    @if($canManage && ! $planLocked && in_array($stage, ['draft', 'cancelled'], true))
                        <div class="d-flex gap-2">
                            @if($event->plots->isNotEmpty())
                                <a href="{{ route('admin.polling.paint', $event) }}" class="mac-btn-secondary">
                                    <i class="bi bi-palette"></i> Paint codes
                                </a>
                            @endif
                            <a href="{{ route('admin.polling.edit', $event) }}" class="mac-btn-secondary">Edit plan</a>
                        </div>
                    @endif
                </div>
                <div class="table-responsive">
                    <table class="mac-table">
                        <thead><tr><th>Location</th><th>Size</th><th class="text-center">Plots</th><th class="text-center">Free</th><th></th></tr></thead>
                        <tbody>
                            @foreach($event->plotSizes as $size)
                                @php $ofSize = $event->plots->where('plot_size_id', $size->id); @endphp
                                <tr>
                                    <td>{{ $size->location }}</td>
                                    <td>{{ $size->size ?: '—' }}</td>
                                    <td class="text-center">{{ $size->quantity }}</td>
                                    <td class="text-center">{{ $ofSize->where('is_allocated', false)->count() }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('admin.polling.plots.by-size', [$event, $size]) }}" class="mac-action-btn">Plots</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mac-card">
                <div class="mac-card-header"><div class="mac-card-title">Code allocations</div></div>
                <div class="table-responsive">
                    <table class="mac-table">
                        <thead><tr><th>Code</th><th>Location</th><th>Size</th><th class="text-center">Plots</th><th class="text-center">Share</th></tr></thead>
                        <tbody>
                            @forelse($event->bidangAllocations as $alloc)
                                <tr>
                                    <td><strong>{{ $alloc->bidang->code }}</strong> <span class="mac-muted">{{ $alloc->bidang->pecahan_bidang ?? $alloc->bidang->name }}</span></td>
                                    <td>{{ $alloc->plotSize->location }}</td>
                                    <td>{{ $alloc->plotSize->size }}</td>
                                    <td class="text-center">{{ $alloc->plots_count }}</td>
                                    <td class="text-center">{{ $alloc->share_percent }}%</td>
                                </tr>
                            @empty
                                <tr><td colspan="5"><div class="mac-empty">No plots are set aside for a craft code — every plot is in the open pool.</div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if($canManage && $event->plotSizes->isNotEmpty() && $event->plots->isEmpty())
                <form method="POST" action="{{ route('admin.polling.generate-plots', $event) }}" class="mt-3">
                    @csrf
                    <button type="submit" class="mac-btn-primary">Generate plots from this plan</button>
                </form>
            @endif
        </div>

        {{-- ── Draw list ───────────────────────────────────────────────── --}}
        <div class="tab-pane fade" id="tab-draw" role="tabpanel">
            <div class="mac-card">
                <div class="table-responsive">
                    <table class="mac-table">
                        <thead><tr><th>Company</th><th>Code</th><th>Size</th><th>Lot</th><th>Drawn</th><th></th></tr></thead>
                        <tbody>
                            @forelse($event->registrations->sortBy('company.name') as $reg)
                                <tr>
                                    <td>{{ $reg->company->name }}</td>
                                    <td>{{ $reg->code ?: '—' }}</td>
                                    <td>{{ $reg->plot_size ?: '—' }}</td>
                                    <td>
                                        @if($reg->plot)
                                            <strong>{{ $reg->plot->plot_code }}</strong>
                                            @if($reg->plot->allocation_type === 'preset')
                                                <span class="mac-badge mac-badge-blue">Preset</span>
                                            @endif
                                        @else
                                            <span class="mac-muted">None yet</span>
                                        @endif
                                    </td>
                                    <td>{{ ($at = $drawnAt[$reg->user_id] ?? null) ? \Carbon\Carbon::parse($at)->format('d M, H:i') : '—' }}</td>
                                    <td class="text-end">
                                        @if($canManage && $reg->plot && $stage !== 'completed')
                                            <form method="POST" action="{{ route('admin.polling.unassign-plot', [$event, $reg->plot]) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="mac-action-btn-danger"
                                                        data-confirm="{{ $reg->plot->allocation_type === 'preset' && ! isset($drawnAt[$reg->user_id])
                                                            ? 'Remove the reservation of '.$reg->plot->plot_code.' for '.$reg->company->name.'?'
                                                            : $reg->company->name.' has DRAWN '.$reg->plot->plot_code.'. Taking it back clears the result, and they can draw again — possibly onto a different lot. Continue?' }}">
                                                    Take back
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6">
                                    <div class="mac-empty">
                                        Nobody is in this draw yet.
                                        <a href="{{ route('admin.events.registrations.index', $event) }}">Open the draw list</a>
                                    </div>
                                </td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ── Plots ───────────────────────────────────────────────────── --}}
        <div class="tab-pane fade" id="tab-plots" role="tabpanel">
            <div class="mac-card">
                <div class="table-responsive">
                    <table class="mac-table">
                        <thead><tr><th>Plot</th><th>Size</th><th>Code</th><th>Held by</th></tr></thead>
                        <tbody>
                            @forelse($event->plots as $plot)
                                <tr>
                                    <td class="fw-semibold">{{ $plot->plot_code }}</td>
                                    <td>{{ $plot->plotSize->location }} {{ $plot->plotSize->size }}</td>
                                    <td>{{ $plot->bidang?->code ?? 'Open pool' }}</td>
                                    <td>
                                        @if($plot->is_allocated)
                                            {{ $plot->company?->name }}
                                            <span class="mac-badge {{ $plot->allocation_type === 'preset' ? 'mac-badge-blue' : 'mac-badge-green' }}">
                                                {{ $plot->allocation_type === 'preset' ? 'Preset' : 'Drawn' }}
                                            </span>
                                        @else
                                            <span class="mac-muted">Free</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4"><div class="mac-empty">No plots built yet.</div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ── Setup ───────────────────────────────────────────────────── --}}
        @if($canManage)
            <div class="tab-pane fade" id="tab-setup" role="tabpanel">
                @if($stage !== 'completed')
                    <div class="mac-card mac-card-padded mb-3">
                        <h3 class="mac-card-title">Set a lot aside</h3>
                        <p class="mac-muted">
                            The company still confirms like everyone else, and the reveal hands them this lot.
                            Setting one aside locks the plan.
                        </p>

                        @php $freePlots = $event->plots->where('is_allocated', false); @endphp
                        @if($unseated->isEmpty() || $freePlots->isEmpty())
                            <p class="mb-0 mac-muted">{{ $unseated->isEmpty() ? 'Everyone in the draw already holds a lot.' : 'No free plot is left.' }}</p>
                        @else
                            <form method="POST" action="{{ route('admin.polling.preset-assign', $event) }}" class="row g-2 align-items-end" id="presetForm">
                                @csrf
                                <div class="col-md-5">
                                    <label class="mac-label" for="registration_id">Company</label>
                                    <select name="registration_id" id="registration_id" class="mac-select" required>
                                        @foreach($unseated as $reg)
                                            <option value="{{ $reg->id }}" data-code="{{ $reg->code }}">{{ $reg->company->name }} @if($reg->code)({{ $reg->code }})@endif</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <label class="mac-label" for="plot_id">Plot</label>
                                    <select name="plot_id" id="plot_id" class="mac-select" required>
                                        @foreach($freePlots as $plot)
                                            <option value="{{ $plot->id }}" data-code="{{ $plot->bidang?->code }}">{{ $plot->plot_code }} — {{ $plot->bidang?->code ?? 'open pool' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="mac-btn-primary w-100">Set aside</button>
                                </div>
                            </form>
                        @endif
                    </div>
                @endif


                @if(in_array($stage, ['draft', 'cancelled'], true) && ! $planLocked)
                    <div class="mac-card mac-card-padded">
                        <h3 class="mac-card-title">Clear the plan</h3>
                        <form method="POST" action="{{ route('admin.polling.destroy', $event) }}">
                            @csrf
                            @method('DELETE')
                            {{-- Every step PollingController::destroyPolling() takes is named here. --}}
                            <button type="submit" class="mac-btn-danger"
                                    data-confirm="Clear the lot plan? Every plot, every location and every code allocation is removed, the voting window is reset, and the draw returns to draft.">
                                Clear the plan
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
// A preset may cross craft codes — that is what an override is for — but it
// should be asked, not stumbled into.
document.getElementById('presetForm')?.addEventListener('submit', function (e) {
    const reg = this.registration_id.selectedOptions[0];
    const plot = this.plot_id.selectedOptions[0];
    const want = reg?.dataset.code, has = plot?.dataset.code;

    if (want && has && want !== has && !confirm(`${reg.text.trim()} holds ${want}, but ${plot.text.trim()} is set aside for ${has}. Set it aside anyway?`)) {
        e.preventDefault();
    }
});
</script>
@endpush
@endsection
