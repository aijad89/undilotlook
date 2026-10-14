@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
@include('partials.mac-styles')

<div class="mac-page">
    @include('partials.admin-alerts')

    <div class="mac-header mb-4">
        <h2>Dashboard</h2>
        <p class="mac-muted mb-0">
            Signed in as {{ $admin->name }} —
            {{ \App\Models\Admin::ROLES[$admin->role] ?? $admin->role }}
        </p>
    </div>

    <div class="mac-card mac-empty">
        <div class="mac-empty-icon" aria-hidden="true"><i class="bi bi-grid-3x3-gap"></i></div>
        <h3 class="mac-empty-title">Nothing to show yet</h3>
        <p class="mac-muted mb-0">
            This screen fills up as the draw does: events, the companies in each
            draw list, and how many have drawn a lot. Until then it is here because
            every signed-in admin is sent to it.
        </p>
    </div>
</div>
@endsection
