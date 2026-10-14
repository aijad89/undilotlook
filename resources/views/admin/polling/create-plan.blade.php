@extends('layouts.admin')

@section('title', 'Create Lot Draw — Locations')

@section('content')
@include('partials.mac-styles')

<div class="mac-page">
    @include('partials.admin-alerts')

    <div class="mac-breadcrumb mb-2">
        <a href="{{ route('admin.polling.index') }}">Lot Draw</a> /
        <a href="{{ route('admin.polling.create') }}">Create</a> / Locations
    </div>

    <div class="mac-header mb-4">
        <h2>Create Lot Draw <span class="mac-badge mac-badge-blue">Step 2 of 2</span></h2>
        <p class="mac-muted mb-0">
            <strong>{{ $event->title }}</strong> —
            voting {{ \Carbon\Carbon::parse($draft['polling_start_date'])->format('d M Y, H:i') }}
            to {{ \Carbon\Carbon::parse($draft['polling_deadline'])->format('d M Y, H:i') }}.
        </p>
    </div>

    @include('admin.polling.partials.plan-reference')

    <form action="{{ route('admin.polling.store') }}" method="POST" id="pollingForm">
        @csrf

        <div class="mac-card">
            <div class="mac-card-header d-flex justify-content-between align-items-center">
                <div class="mac-card-title">
                    <i class="bi bi-grid"></i> Locations &amp; Code Allocations
                    <span class="mac-badge mac-badge-gray ms-2"><span id="totalPlotsDisplay">0</span> plots</span>
                </div>
                <button type="button" class="mac-btn-secondary" id="addSizeBtn">
                    <i class="bi bi-plus"></i> Add Location
                </button>
            </div>
            <div class="mac-card-padded">
                {{-- Read by the script; the server counts total_plots itself. --}}
                <input type="hidden" id="totalPlotsInput" value="0">
                @include('admin.polling.partials.plan-fields', ['event' => null])
            </div>
        </div>

        <div class="d-flex justify-content-between mt-4">
            <a href="{{ route('admin.polling.create') }}" class="mac-btn-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
            <button type="submit" class="mac-btn-primary">
                <i class="bi bi-save"></i> Create Lot Draw
            </button>
        </div>
    </form>
</div>

@push('scripts')
    @include('admin.polling.partials.plan-scripts')
@endpush
@endsection
