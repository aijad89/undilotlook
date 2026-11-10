@extends('layouts.public')

@section('title', $event->title.' — '.\App\Models\Setting::get('site_name', 'undilot'))
@section('page-title', 'Event')
@section('own-heading', true)

@push('styles')
@include('partials.public-event-styles')
<style>
    .evd-back { display: inline-flex; align-items: center; gap: 6px; min-height: 40px; margin-bottom: 16px; color: var(--ui-text-2); font-size: 1rem; font-weight: 600; text-decoration: none; }
    .evd-back:hover { color: var(--ui-blue-text); }
    .evd-banner { display: block; width: 100%; max-height: 360px; margin-bottom: 28px; object-fit: cover; border-radius: var(--ui-r-lg); }
    .evd-grid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(300px, 380px); gap: 32px; align-items: start; }
    .evd-head { display: flex; gap: 18px; align-items: flex-start; margin-bottom: 20px; }
    .evd-head h1 { margin: 0 0 10px; font-size: 2.25rem; font-weight: 700; line-height: 1.15; letter-spacing: -0.02em; color: var(--ui-text); }
    .evd-head .ev-date { width: 68px; }
    .evd-head .ev-date-day { font-size: 1.75rem; }
    .evd-about { max-width: 72ch; margin: 0; font-size: 1rem; line-height: 1.7; color: var(--ui-text-2); white-space: pre-line; }
    .evd-card { padding: 22px; background: var(--ui-surface); border: 1px solid var(--ui-border); border-radius: var(--ui-r-lg); box-shadow: var(--ui-shadow-1); }
    .evd-fact { display: flex; gap: 12px; padding: 14px 0; border-bottom: 1px solid var(--ui-sep); }
    .evd-fact:first-child { padding-top: 0; }
    .evd-fact:last-of-type { border-bottom: none; }
    .evd-fact > i { margin-top: 2px; font-size: 1.125rem; color: var(--ui-muted); }
    .evd-fact-label { margin-bottom: 2px; font-size: 0.875rem; font-weight: 600; color: var(--ui-muted); }
    .evd-fact-value { font-size: 1rem; color: var(--ui-text); }
    .evd-cta { display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; min-height: 48px; margin-top: 14px; border-radius: var(--ui-r); background: var(--ui-blue); color: #fff; font-size: 1rem; font-weight: 600; text-decoration: none; }
    .evd-cta:hover { background: var(--ui-blue-hover); color: #fff; }
    .evd-note { margin: 12px 0 0; font-size: 0.9375rem; line-height: 1.5; color: var(--ui-muted); }
    @media (max-width: 860px) { .evd-grid { grid-template-columns: 1fr; gap: 24px; } }
    @media (max-width: 640px) {
        .ev-page { padding-top: 16px; }
        .evd-head h1 { font-size: 1.5rem; }
        .evd-head .ev-date { width: 56px; }
        .evd-head .ev-date-day { font-size: 1.5rem; }
    }
</style>
@endpush

@php
    $drawOpen = $event->polling_status === 'polling' && $event->polling_deadline && ! $event->polling_deadline->isPast();
@endphp

@section('content')
<div class="ev-page">
    <a href="{{ route('home') }}" class="evd-back"><i class="bi bi-arrow-left" aria-hidden="true"></i> All events</a>

    @if($event->banner_url)
        <img class="evd-banner" src="{{ $event->banner_url }}" alt="">
    @endif

    <div class="evd-grid">
        <div>
            <div class="evd-head">
                <div class="ev-date" aria-hidden="true">
                    <span class="ev-date-month">{{ $event->start_date->format('M') }}</span>
                    <span class="ev-date-day">{{ $event->start_date->format('d') }}</span>
                </div>
                <div style="min-width:0;">
                    <h1>{{ $event->title }}</h1>
                    @include('partials.public-event-stage', ['event' => $event])
                </div>
            </div>

            @if($event->description)
                <p class="evd-about">{{ $event->description }}</p>
            @else
                <p class="evd-about" style="color:var(--ui-muted);">The office has not added a description for this event.</p>
            @endif
        </div>

        <aside class="evd-card" aria-label="Event details">
            <div class="evd-fact">
                <i class="bi bi-calendar3" aria-hidden="true"></i>
                <div>
                    <div class="evd-fact-label">When</div>
                    <div class="evd-fact-value">
                        {{ $event->start_date->format('d M Y') }}@unless($event->end_date->isSameDay($event->start_date)) – {{ $event->end_date->format('d M Y') }}@endunless
                    </div>
                </div>
            </div>
            <div class="evd-fact">
                <i class="bi bi-geo-alt" aria-hidden="true"></i>
                <div>
                    <div class="evd-fact-label">Where</div>
                    <div class="evd-fact-value">{{ $event->location }}@if($event->state), {{ $event->state }}@endif</div>
                </div>
            </div>
            @if($event->polling_start_date && $event->polling_deadline)
                <div class="evd-fact">
                    <i class="bi bi-grid-3x3-gap" aria-hidden="true"></i>
                    <div>
                        <div class="evd-fact-label">Lot draw</div>
                        <div class="evd-fact-value">
                            {{ $event->polling_start_date->format('d M Y, h:i A') }}<br>
                            to {{ $event->polling_deadline->format('d M Y, h:i A') }}
                        </div>
                    </div>
                </div>
            @endif

            @if($drawOpen)
                @auth
                    <a href="{{ route('dashboard') }}" class="evd-cta"><i class="bi bi-check-circle" aria-hidden="true"></i> Go to my dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="evd-cta"><i class="bi bi-box-arrow-in-right" aria-hidden="true"></i> Sign in to confirm your lot</a>
                @endauth
            @endif
            <p class="evd-note">Only companies the office has put in this event's lot draw can confirm a lot.</p>
        </aside>
    </div>
</div>
@endsection
