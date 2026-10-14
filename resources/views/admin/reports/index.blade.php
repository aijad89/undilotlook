@extends('layouts.admin')

@section('title', 'Reports & Export')

@section('content')
@include('partials.mac-styles')

@php
    $global = auth('admin')->user()->hasGlobalAccess();

    // One card per export. Each names the filters it reads, so a filter the
    // export ignores is never offered.
    $reports = [
        ['admin.reports.registrations', 'Draw lists', 'card-checklist', 'Every company in each draw list: craft code, booth size, and the lot it holds.', ['event', 'zone']],
        ['admin.reports.lot-draw', 'Lot results', 'grid-3x3-gap', 'Who holds which lot, and whether it was drawn or set aside.', ['event', 'zone']],
        ['admin.reports.companies', 'Companies', 'building', 'Every company, its owner, zone and craft category.', ['zone', 'category', 'active']],
        ['admin.reports.event-summary', 'Event summary', 'calendar-event', 'One row per event: how full its draw is and how far it has got.', ['dates']],
    ];
@endphp

<div class="mac-page">
    @include('partials.admin-alerts')

    <div class="mac-header mb-4">
        <h2>Reports &amp; Export</h2>
        <p class="mac-muted mb-0">
            CSV files that open in Excel.
            @unless($global) Each covers the companies in your zone, {{ auth('admin')->user()->state }}. @endunless
        </p>
    </div>

    <div class="d-flex flex-column gap-3">
        @foreach($reports as [$route, $title, $icon, $blurb, $filters])
            <div class="mac-card">
                <div class="mac-card-header">
                    <div class="mac-card-title"><i class="bi bi-{{ $icon }}"></i> {{ $title }}</div>
                    <div class="mac-muted mt-1">{{ $blurb }}</div>
                </div>
                <form method="GET" action="{{ route($route) }}" class="mac-card-padded d-flex align-items-end gap-3 flex-wrap">
                    @if(in_array('event', $filters))
                        <div>
                            <label class="mac-label" for="{{ $route }}-event">Event</label>
                            <select name="event_id" id="{{ $route }}-event" class="mac-select">
                                <option value="">Every event</option>
                                @foreach($events as $event)
                                    <option value="{{ $event->id }}">{{ $event->title }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    @if(in_array('zone', $filters) && $global)
                        <div>
                            <label class="mac-label" for="{{ $route }}-zone">Zone</label>
                            <select name="state" id="{{ $route }}-zone" class="mac-select">
                                <option value="">Every zone</option>
                                @foreach($zones as $zone)
                                    <option value="{{ $zone }}">{{ $zone }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    @if(in_array('category', $filters))
                        <div>
                            <label class="mac-label" for="{{ $route }}-category">Craft category</label>
                            <select name="bidang_id" id="{{ $route }}-category" class="mac-select">
                                <option value="">Every category</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    @if(in_array('active', $filters))
                        <div>
                            <label class="mac-label" for="{{ $route }}-active">Active</label>
                            <select name="active" id="{{ $route }}-active" class="mac-select">
                                <option value="">Both</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    @endif
                    @if(in_array('dates', $filters))
                        <div>
                            <label class="mac-label" for="{{ $route }}-from">Starting from</label>
                            <input type="date" name="date_from" id="{{ $route }}-from" class="mac-input">
                        </div>
                        <div>
                            <label class="mac-label" for="{{ $route }}-to">Starting by</label>
                            <input type="date" name="date_to" id="{{ $route }}-to" class="mac-input">
                        </div>
                    @endif
                    <button type="submit" class="mac-btn-primary"><i class="bi bi-download"></i> Export CSV</button>
                </form>
            </div>
        @endforeach
    </div>
</div>
@endsection
