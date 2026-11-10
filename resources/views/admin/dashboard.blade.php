@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
@include('partials.mac-styles')

<div class="mac-page">
    @include('partials.admin-alerts')

    <div class="mac-header mb-4 d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h2>Dashboard</h2>
            <p class="mac-muted mb-0">
                Signed in as {{ $admin->name }} —
                {{ \App\Models\Admin::ROLES[$admin->role] ?? $admin->role }}@unless($global) · {{ $admin->state }}@endunless
            </p>
        </div>

        {{-- Shortcuts: each one only for the roles its own route lets through. --}}
        <div class="d-flex flex-wrap gap-2">
            @can('manage-events')
                <a href="{{ route('admin.events.create') }}" class="mac-btn-secondary"><i class="bi bi-calendar-plus me-1"></i> New event</a>
            @endcan
            <a href="{{ route('admin.companies.create') }}" class="mac-btn-secondary"><i class="bi bi-building-add me-1"></i> Add company</a>
            @can('send-announcements')
                <a href="{{ route('admin.announcements.create') }}" class="mac-btn-secondary"><i class="bi bi-megaphone me-1"></i> Announce</a>
            @endcan
        </div>
    </div>

    {{-- ── Needs attention ─────────────────────────────────────────── --}}
    <section class="mac-card mac-card-padded mb-3" aria-labelledby="dash-attention">
        <h3 class="mac-card-title" id="dash-attention">Needs attention</h3>

        @forelse($attention as [$tone, $text, $link])
            <a href="{{ $link }}" class="dash-row dash-{{ $tone }}">
                <i class="bi {{ $tone === 'bad' ? 'bi-x-octagon' : ($tone === 'warn' ? 'bi-exclamation-triangle' : 'bi-info-circle') }}" aria-hidden="true"></i>
                <span>{{ $text }}</span>
                <i class="bi bi-chevron-right ms-auto" aria-hidden="true"></i>
            </a>
        @empty
            <p class="dash-row dash-ok mb-0">
                <i class="bi bi-check-circle" aria-hidden="true"></i>
                <span>All clear — nothing waiting on you.</span>
            </p>
        @endforelse
    </section>

    <div class="dash-grid">
        {{-- ── Events ─────────────────────────────────────────────── --}}
        <section class="mac-card mac-card-padded" aria-labelledby="dash-events">
            <div class="d-flex justify-content-between align-items-baseline">
                <h3 class="mac-card-title" id="dash-events">Events</h3>
                <a href="{{ route('admin.events.index') }}" class="mac-text-sm">All events</a>
            </div>

            @forelse($events as $event)
                @php
                    // The next date that matters for this event.
                    [$nextLabel, $nextAt] = match (true) {
                        $event->polling_status === 'draft' && $event->polling_start_date?->isFuture() => ['Draw opens', $event->polling_start_date],
                        $event->polling_status === 'polling' && $event->polling_deadline?->isFuture() => ['Draw closes', $event->polling_deadline],
                        (bool) $event->start_date?->isFuture() => ['Starts', $event->start_date],
                        default => [null, null],
                    };
                    $percent = $event->on_list > 0 ? (int) round($event->confirmed / $event->on_list * 100) : 0;
                @endphp
                <div class="dash-event">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <a href="{{ route('admin.polling.show', $event) }}" class="dash-event-title">{{ $event->title }}</a>
                        <span class="d-flex gap-1 flex-shrink-0">
                            <span class="mac-status {{ $event->status_colour }}">{{ $event->status_label }}</span>
                            <span class="mac-status {{ \App\Models\Event::pollingStatusColour($event->polling_status) }}">
                                Draw: {{ \App\Models\Event::pollingStatusLabel($event->polling_status) }}
                            </span>
                        </span>
                    </div>

                    <div class="mac-muted mac-text-sm">
                        {{ $event->start_date?->format('j M Y') ?? 'No date' }}@if($event->location) · {{ $event->location }}@endif
                        @if($nextLabel) · <strong>{{ $nextLabel }} {{ $nextAt->diffForHumans() }}</strong>@endif
                    </div>

                    <div class="dash-figures mac-text-sm">
                        <a href="{{ route('admin.events.registrations.index', $event) }}">{{ $event->on_list }} on draw list</a>
                        <span>{{ $event->coded }} with a code</span>
                        <span>{{ $event->confirmed }} confirmed</span>
                        <span>{{ $event->plots_taken }}/{{ $event->plots_total }} plots taken</span>
                    </div>

                    @if($event->polling_status === 'polling' && $event->on_list > 0)
                        <div class="dash-bar" role="img" aria-label="{{ $percent }}% confirmed">
                            <span style="width: {{ $percent }}%"></span>
                        </div>
                        <div class="mac-muted mac-text-sm">{{ $percent }}% confirmed · {{ $event->on_list - $event->confirmed }} waiting</div>
                    @endif
                </div>
            @empty
                <p class="mac-muted mac-text-sm mb-0">No upcoming events.</p>
            @endforelse
        </section>

        {{-- ── Companies ──────────────────────────────────────────── --}}
        <section class="mac-card mac-card-padded" aria-labelledby="dash-companies">
            <div class="d-flex justify-content-between align-items-baseline">
                <h3 class="mac-card-title" id="dash-companies">Companies</h3>
                <a href="{{ route('admin.companies.index') }}" class="mac-text-sm">All companies</a>
            </div>

            <div class="dash-totals">
                <div><strong>{{ $companyStats['active'] }}</strong><span>active</span></div>
                <div><strong>{{ $companyStats['inactive'] }}</strong><span>inactive</span></div>
                <div><strong>{{ $companyStats['new'] }}</strong><span>new this month</span></div>
            </div>

            @php $max = max(1, $companyStats['byCategory']->max() ?? 1); @endphp
            <h4 class="dash-sub">By craft category</h4>
            @forelse($companyStats['byCategory'] as $name => $n)
                <div class="dash-split">
                    <span>{{ $name }}</span>
                    <span class="dash-split-bar"><span style="width: {{ round($n / $max * 100) }}%"></span></span>
                    <strong>{{ $n }}</strong>
                </div>
            @empty
                <p class="mac-muted mac-text-sm mb-0">No company has a craft category yet.</p>
            @endforelse

            @if($global && $companyStats['byZone']->isNotEmpty())
                @php $maxZone = max(1, $companyStats['byZone']->max()); @endphp
                <h4 class="dash-sub">By zone</h4>
                @foreach($companyStats['byZone'] as $zone => $n)
                    <a class="dash-split" href="{{ route('admin.companies.index', ['state' => $zone]) }}">
                        <span>{{ $zone }}</span>
                        <span class="dash-split-bar"><span style="width: {{ round($n / $maxZone * 100) }}%"></span></span>
                        <strong>{{ $n }}</strong>
                    </a>
                @endforeach
            @endif
        </section>
    </div>
</div>

<style>
    .dash-row { display: flex; align-items: center; gap: 10px; padding: 10px 12px; margin-top: 6px;
        border-radius: var(--ui-r); font-size: 0.875rem; text-decoration: none; color: var(--ui-text); }
    .dash-row:hover { filter: brightness(0.97); }
    .dash-bad  { background: var(--ui-bad-tint);  } .dash-bad  > .bi:first-child { color: var(--ui-bad-text); }
    .dash-warn { background: var(--ui-warn-tint); } .dash-warn > .bi:first-child { color: var(--ui-warn-text); }
    .dash-info { background: var(--ui-blue-tint); } .dash-info > .bi:first-child { color: var(--ui-blue-text); }
    .dash-ok   { background: var(--ui-ok-tint); color: var(--ui-ok-text); }

    .dash-grid { display: grid; grid-template-columns: minmax(0, 3fr) minmax(0, 2fr); gap: 16px; }
    @media (max-width: 860px) { .dash-grid { grid-template-columns: minmax(0, 1fr); } }

    .dash-event { padding: 12px 0; border-top: 1px solid var(--ui-sep); }
    .dash-event:first-of-type { border-top: 0; }
    .dash-event-title { font-weight: 650; color: var(--ui-text); text-decoration: none; }
    .dash-event-title:hover { text-decoration: underline; }
    .dash-figures { display: flex; flex-wrap: wrap; gap: 4px 14px; margin-top: 6px; color: var(--ui-text-2); }
    .dash-bar { height: 8px; margin-top: 8px; border-radius: 99px; background: var(--ui-neutral-tint); overflow: hidden; }
    .dash-bar > span { display: block; height: 100%; background: var(--ui-ok-text); }

    .dash-totals { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px; margin: 8px 0 4px; }
    .dash-totals > div { padding: 10px; border-radius: var(--ui-r); background: var(--ui-neutral-tint); }
    .dash-totals strong { display: block; font-size: 1.5rem; line-height: 1.2; }
    .dash-totals span { font-size: 0.8125rem; color: var(--ui-muted); }

    .dash-sub { margin: 16px 0 6px; font-size: 0.8125rem; font-weight: 650; color: var(--ui-text-2); }
    .dash-split { display: grid; grid-template-columns: minmax(0, 1.4fr) minmax(0, 1fr) auto; align-items: center;
        gap: 10px; padding: 4px 0; font-size: 0.8125rem; color: var(--ui-text); text-decoration: none; }
    .dash-split > span:first-child { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .dash-split-bar { height: 6px; border-radius: 99px; background: var(--ui-neutral-tint); overflow: hidden; }
    .dash-split-bar > span { display: block; height: 100%; background: var(--ui-blue-text); }
</style>
@endsection
