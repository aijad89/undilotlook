@extends('layouts.frontend')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
@include('partials.apple-styles')

<div class="ap-card ap-card-padded mb-3">
    <h2 style="font-size:1.1rem;font-weight:600;margin-bottom:6px;">
        Selamat datang, {{ $user->name }}
    </h2>
    <p class="text-muted mb-0">
        @if($entries->isEmpty())
            Acara yang anda masuk cabutan akan muncul di sini, bersama lot yang anda
            tarik. Belum ada apa-apa buat masa ini.
        @else
            Cabutan lot yang syarikat anda sertai.
        @endif
    </p>
</div>

@foreach($entries as $entry)
    @php
        $event = $entry->event;
        $hasDrawn = isset($drawn[$event->id]);
        $open = $event->polling_status === 'polling' && ! $event->polling_deadline?->isPast();
    @endphp
    <div class="ap-card ap-card-padded mb-3">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <div style="font-weight:600;">{{ $event->title }}</div>
                <div class="text-muted" style="font-size:0.85rem;">
                    {{ $event->location }} · {{ $event->start_date->format('d M Y') }}
                </div>
            </div>

            @if($hasDrawn && $entry->plot)
                <a href="{{ route('zvote.index', $event) }}" class="ap-btn ap-btn-outline ap-btn-sm">
                    Lot <strong class="ms-1">{{ $entry->plot->plot_code }}</strong>
                </a>
            @elseif($open)
                <a href="{{ route('zvote.index', $event) }}" class="ap-btn ap-btn-primary ap-btn-sm">
                    <i class="bi bi-check-circle me-1"></i> Sahkan lot
                </a>
            @endif
        </div>

        <div class="mt-2" style="font-size:0.85rem;">
            @if($hasDrawn)
                <span class="ap-badge ap-badge-green">Lot disahkan</span>
            @elseif($open)
                Cabutan dibuka sehingga {{ $event->polling_deadline->format('d M Y, h:i A') }}.
            @elseif($event->polling_status === 'draft' && $event->polling_start_date)
                Cabutan dibuka {{ $event->polling_start_date->format('d M Y, h:i A') }}.
            @elseif($event->polling_status === 'draft')
                Anda dalam senarai cabutan. Tarikh cabutan lot belum ditetapkan.
            @elseif($entry->plot)
                Lot anda: <strong>{{ $entry->plot->plot_code }}</strong>
            @else
                Cabutan telah ditutup. Pejabat akan menetapkan lot anda.
            @endif
        </div>
    </div>
@endforeach
@endsection
