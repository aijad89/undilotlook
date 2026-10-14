@extends('layouts.admin')

@section('title', 'Create Lot Draw')

@section('content')
@include('partials.mac-styles')

<div class="mac-page">
    @include('partials.admin-alerts')

    <div class="mac-breadcrumb mb-2">
        <a href="{{ route('admin.polling.index') }}">Lot Draw</a> <span class="sep" aria-hidden="true">›</span> Create
    </div>

    <div class="mac-header mb-4">
        <h2>Create Lot Draw <span class="mac-badge mac-badge-blue">Step 1 of 2</span></h2>
        <p class="mac-muted mb-0">When the draw runs. Nothing is saved until step 2.</p>
    </div>

    @if($events->isEmpty())
        <div class="mac-card mac-card-padded">
            <p class="mb-0">
                Every event already has a lot plan, or none is in draft.
                <a href="{{ route('admin.events.index') }}">Events</a>
            </p>
        </div>
    @else
        <form action="{{ route('admin.polling.store-settings') }}" method="POST" id="pollingForm">
            @csrf

            <div class="mac-card">
                <div class="mac-card-header">
                    <div class="mac-card-title"><i class="bi bi-calendar-event"></i> Lot Draw Settings</div>
                </div>
                <div class="mac-card-padded">
                    @include('admin.polling.partials.settings-fields', ['event' => null])
                </div>
            </div>

            <div class="d-flex justify-content-between mt-4">
                <a href="{{ route('admin.polling.index') }}" class="mac-btn-secondary">Cancel</a>
                <button type="submit" class="mac-btn-primary">
                    Next: Locations <i class="bi bi-arrow-right"></i>
                </button>
            </div>
        </form>
    @endif
</div>

@push('scripts')
    @include('admin.polling.partials.settings-scripts')
@endpush
@endsection
