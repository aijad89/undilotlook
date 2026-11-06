@extends('layouts.frontend')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
@include('partials.apple-styles')

@php
    // The usahawan's words for the four stops.
    $stopLabels = ['draft' => 'Draft', 'polling' => 'Open', 'closed' => 'Closed', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];
@endphp

<div class="ap-page">
    <div class="ap-page-header">
        <div>
            <h2 class="ap-page-title">Welcome, {{ $user->name }}</h2>
            <p class="ap-page-subtitle">
                @if($entries->isEmpty())
                    Events whose draw your company is in appear here, with the lot you
                    draw. There is nothing here yet.
                @else
                    The lot draws your company is in.
                @endif
            </p>
        </div>
    </div>

    <div class="ap-draw-grid">
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
                    <span class="ap-badge ap-badge-green">Lot confirmed</span>
                @endif
            </div>

            {{-- From Open on: a draft draw is the office's, not yet the usahawan's. --}}
            @include('partials.draw-stepper', ['status' => $event->polling_status, 'labels' => $stopLabels, 'stops' => ['polling', 'closed', 'completed']])

            <div class="ap-draw-next">
                {{-- A drawn lot is named once, on its own link. --}}
                @unless($hasDrawn && $entry->plot)
                <span>
                    @if($open)
                        The draw is open until {{ $event->polling_deadline->format('d M Y, h:i A') }}.
                    @elseif($event->polling_status === 'draft' && $event->polling_start_date)
                        The draw opens {{ $event->polling_start_date->format('d M Y, h:i A') }}.
                    @elseif($event->polling_status === 'draft')
                        You are in the draw. The draw date has not been set yet.
                    @elseif($entry->plot)
                        Your lot: <strong>{{ $entry->plot->plot_code }}</strong>
                    @else
                        The draw has closed. The office will assign your lot.
                    @endif
                </span>
                @endunless

                @if($hasDrawn && $entry->plot)
                    {{-- Says what the button is; the lot number itself stays on the button. --}}
                    <span>Congratulations, you have your lot. Your lot number for this event is:</span>
                    <a href="{{ route('zvote.index', $event) }}" class="ap-btn ap-btn-outline ap-draw-lot-link">
                        <i class="bi bi-geo-alt-fill" aria-hidden="true"></i>Lot <strong class="ms-1">{{ $entry->plot->plot_code }}</strong>
                    </a>
                @elseif($open)
                    <a href="{{ route('zvote.index', $event) }}" class="ap-btn ap-btn-primary ap-btn-lg">
                        <i class="bi bi-check-circle" aria-hidden="true"></i> Confirm Lot
                    </a>
                @endif
            </div>
        </article>
    @endforeach
    </div>
</div>
@endsection
