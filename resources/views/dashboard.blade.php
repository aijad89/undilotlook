@extends('layouts.frontend')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
@include('partials.apple-styles')

@php
    // The usahawan's words for the four stops.
    $stopLabels = ['draft' => 'Draf', 'polling' => 'Dibuka', 'closed' => 'Ditutup', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'];
@endphp

<div class="ap-page">
    <div class="ap-page-header">
        <div>
            <h2 class="ap-page-title">Selamat datang, {{ $user->name }}</h2>
            <p class="ap-page-subtitle">
                @if($entries->isEmpty())
                    Acara yang anda masuk cabutan akan muncul di sini, bersama lot yang anda
                    tarik. Belum ada apa-apa buat masa ini.
                @else
                    Cabutan lot yang syarikat anda sertai.
                @endif
            </p>
        </div>
    </div>

    @foreach($entries as $entry)
        @php
            $event = $entry->event;
            $hasDrawn = isset($drawn[$event->id]);
            $open = $event->polling_status === 'polling' && ! $event->polling_deadline?->isPast();
        @endphp
        <article class="ap-card ap-draw mb-3">
            <div class="ap-draw-head">
                <div>
                    <h3 class="ap-draw-title">{{ $event->title }}</h3>
                    <div class="ap-draw-meta">
                        <span><i class="bi bi-geo-alt" aria-hidden="true"></i>{{ $event->location }}</span>
                        <span><i class="bi bi-calendar3" aria-hidden="true"></i>{{ $event->start_date->format('d M Y') }}</span>
                    </div>
                </div>
                @if($hasDrawn)
                    <span class="ap-badge ap-badge-green">Lot disahkan</span>
                @endif
            </div>

            @include('partials.draw-stepper', ['status' => $event->polling_status, 'labels' => $stopLabels])

            <div class="ap-draw-next">
                {{-- A drawn lot is named once, on its own link. --}}
                @unless($hasDrawn && $entry->plot)
                <span>
                    @if($open)
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
                </span>
                @endunless

                @if($hasDrawn && $entry->plot)
                    <a href="{{ route('zvote.index', $event) }}" class="ap-btn ap-btn-outline ap-draw-lot-link">
                        <i class="bi bi-geo-alt-fill" aria-hidden="true"></i>Lot <strong class="ms-1">{{ $entry->plot->plot_code }}</strong>
                    </a>
                @elseif($open)
                    <a href="{{ route('zvote.index', $event) }}" class="ap-btn ap-btn-primary ap-btn-lg">
                        <i class="bi bi-check-circle" aria-hidden="true"></i> Sahkan lot
                    </a>
                @endif
            </div>
        </article>
    @endforeach
</div>
@endsection
