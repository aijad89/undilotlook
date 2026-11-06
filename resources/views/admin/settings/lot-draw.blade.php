@extends('layouts.admin')

@section('title', 'Lot Draw')

@section('content')
@include('partials.mac-styles')

<div class="mac-page">
    @include('partials.admin-alerts')

    <div class="mac-header mb-4">
        <h2>Lot Draw</h2>
        <div class="mac-breadcrumb"><a href="{{ route('admin.dashboard') }}">Dashboard</a> <span class="sep" aria-hidden="true">›</span> Setting <span class="sep" aria-hidden="true">›</span> Lot Draw</div>
        <p class="mac-muted mb-0">How the lot draw looks to participants, for every event.</p>
    </div>

    <div class="mac-card mac-card-padded mb-3">
        <h3 class="mac-card-title">Lot reveal</h3>
        <p class="mac-muted mac-text-sm">
            What a participant sees when they confirm their lot. Presentation only: the
            lot is already decided before the reveal plays.
        </p>

        <form method="POST" action="{{ route('admin.settings.lot-draw.update') }}" class="d-flex flex-wrap gap-2 align-items-end">
            @csrf
            @method('PUT')
            <div>
                <label class="mac-label" for="draw_animation">Reveal</label>
                <select id="draw_animation" name="draw_animation" class="mac-select" style="min-width:240px">
                    @foreach(\App\Models\Event::DRAW_ANIMATIONS as $key => $label)
                        <option value="{{ $key }}" @selected($reveal === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="mac-btn-primary">Save</button>
        </form>
    </div>
</div>
@endsection
