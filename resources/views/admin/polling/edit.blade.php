@extends('layouts.admin')

@section('title', 'Edit Lot Draw')

@section('content')
@include('partials.mac-styles')

<div class="mac-page">
    @include('partials.admin-alerts')

    <div class="mac-breadcrumb mb-2">
        <a href="{{ route('admin.polling.index') }}">Lot Draw</a> /
        <a href="{{ route('admin.polling.show', $event) }}">{{ $event->title }}</a> / Edit
    </div>

    <div class="mac-header mb-4">
        <h2>Edit Lot Draw</h2>
        <p class="mac-muted mb-0">
            Saving rebuilds every plot from this plan. It is refused once any lot is held.
        </p>
    </div>

    {{-- One page, not the wizard's two: the event already exists, so there is no
         order to walk through. The fields are the create form's partials. --}}
    <form action="{{ route('admin.polling.update', $event) }}" method="POST" id="pollingForm">
        @csrf
        @method('PUT')

        <div class="mac-card mb-4">
            <div class="mac-card-header">
                <div class="mac-card-title"><i class="bi bi-calendar-event"></i> Lot Draw Settings</div>
            </div>
            <div class="mac-card-padded">
                @include('admin.polling.partials.settings-fields', ['event' => $event, 'events' => null])
            </div>
        </div>

        @include('admin.polling.partials.plan-reference')

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
                <input type="hidden" id="totalPlotsInput" value="{{ $event->total_plots }}">
                @include('admin.polling.partials.plan-fields', ['event' => $event])
            </div>
        </div>

        <div class="d-flex justify-content-between mt-4">
            <a href="{{ route('admin.polling.show', $event) }}" class="mac-btn-secondary">Cancel</a>
            <button type="submit" class="mac-btn-primary">
                <i class="bi bi-save"></i> Save and rebuild plots
            </button>
        </div>
    </form>
</div>

@push('scripts')
    @include('admin.polling.partials.settings-scripts')
    @include('admin.polling.partials.plan-scripts')
@endpush
@endsection
