@extends('layouts.frontend')

@section('title', $event->title)
@section('page-title', 'Event summary')

@section('content')
@include('partials.apple-styles')

{{-- The summary of one past event. Not built yet: this holds its place, so
     the link from Past events already goes where it will. --}}
<div class="ap-page ap-dash">
    <div class="ap-page-header">
        <div>
            <h2 class="ap-page-title">{{ $event->title }}</h2>
            <p class="ap-page-subtitle ap-tabular">
                {{ $event->start_date->format('d M Y') }}@unless($event->end_date->isSameDay($event->start_date)) – {{ $event->end_date->format('d M Y') }}@endunless
                @if($event->location) · {{ $event->location }}@endif
            </p>
        </div>
    </div>

    <div class="ap-card ap-empty">
        <i class="bi bi-cone-striped ap-empty-icon" aria-hidden="true"></i>
        <div class="ap-empty-title">Page under construction</div>
        <p class="ap-empty-text mb-0">The summary for this event is not ready yet. Please check back later.</p>
    </div>
</div>
@endsection
