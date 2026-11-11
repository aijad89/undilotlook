@extends('layouts.frontend')

@section('title', 'Past events')
@section('page-title', 'Past events')

@section('content')
@include('partials.apple-styles')

<div class="ap-page ap-dash">
    <div class="ap-page-header">
        <div>
            <h2 class="ap-page-title">Past events</h2>
            <p class="ap-page-subtitle">The events your company took part in that have ended, latest first, with what you answered when you asked to join each.</p>
        </div>
    </div>

    <section class="ap-card ap-table-card mb-4" aria-label="Past events">
        @if($entries->isEmpty())
            <div class="ap-empty">
                <i class="bi bi-clock-history ap-empty-icon" aria-hidden="true"></i>
                <div class="ap-empty-title">No past events yet</div>
                <p class="ap-empty-text mb-0">Once an event your company took part in has ended, it is listed here with the lot you had.</p>
            </div>
        @else
            {{-- Wide on a desk, so it scrolls inside its card rather than the page. --}}
            <div class="table-responsive">
            <table class="ap-table ap-table-stack ap-past-table">
                <thead>
                    <tr>
                        <th scope="col">Event</th>
                        <th scope="col">Booth</th>
                        <th scope="col">Lot</th>
                        <th scope="col">Contact person</th>
                        <th scope="col">Recognition</th>
                        <th scope="col">Certificates</th>
                        <th scope="col">Photos</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($entries as $entry)
                        @php
                            $event = $entry->event;
                            // As the company answered when it asked to join this event.
                            $a = $answers[$event->id] ?? null;
                            $cert = fn ($c) => ! empty($c['number'])
                                ? $c['number'].(! empty($c['end']) ? ', until '.\Illuminate\Support\Carbon::parse($c['end'])->format('d M Y') : '')
                                : null;
                        @endphp
                        <tr>
                            <td data-label="Event">
                                <a href="{{ route('events.show', $event) }}">{{ $event->title }}</a>
                                <span class="ap-past-meta ap-tabular">
                                    {{ $event->start_date->format('d M Y') }}@unless($event->end_date->isSameDay($event->start_date)) – {{ $event->end_date->format('d M Y') }}@endunless
                                    @if($event->location) · {{ $event->location }}@endif
                                </span>
                            </td>
                            <td data-label="Booth" class="ap-tabular">{{ $entry->plot_size }}</td>
                            <td data-label="Lot">
                                @if($entry->plot)
                                    {{-- One unit, so a phone's label-and-value line keeps them together. --}}
                                    <span><span class="ap-lot-code">{{ $entry->plot->plot_code }}</span>@if(isset($drawn[$event->id])) · <a href="{{ route('zvote.slip', $event) }}">Slip</a>@endif</span>
                                @else
                                    <span class="ap-muted">No lot</span>
                                @endif
                            </td>
                            @if($a)
                                <td data-label="Contact person">
                                    <span>{{ $a['contact_name'] ?? '—' }}@if(! empty($a['contact_phone']))<span class="ap-past-meta ap-tabular">{{ $a['contact_phone'] }}</span>@endif</span>
                                </td>
                                <td data-label="Recognition">{{ \App\Models\Company::RECOGNITIONS[$a['recognition'] ?? ''] ?? '—' }}</td>
                                <td data-label="Certificates">
                                    <span>
                                        <span class="ap-tabular">Classification: {{ $cert($a['classification_cert'] ?? null) ?? 'none' }}</span>
                                        @if(! empty($a['quality']))
                                            <span class="ap-past-meta ap-tabular">Quality: {{ $cert($a['quality_cert'] ?? null) ?? 'not applicable' }}</span>
                                        @endif
                                    </span>
                                </td>
                                <td data-label="Photos" class="ap-tabular">{{ count($a['product_images'] ?? []) ?: '—' }}</td>
                            @else
                                {{-- No request: the office put the company on the list itself. --}}
                                <td data-label="Answers" colspan="4"><span class="ap-muted">Added by the office, no answers sent</span></td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        @endif
    </section>

    @include('partials.company-record', ['company' => $company])
</div>
@endsection
