@extends('layouts.admin')

@section('title', 'Edit Event')

@section('content')
@include('partials.mac-styles')

<div class="mac-page">
    @include('partials.admin-alerts')

    <div class="mac-breadcrumb mb-2">
        <a href="{{ route('admin.events.index') }}">Events</a> <span class="sep" aria-hidden="true">›</span> {{ $event->title }}
    </div>

    <div class="mac-header mb-4">
        <h2>Edit Event</h2>
        <p class="mac-muted mb-0">
            Draw status: {{ $event->polling_status_label }} —
            changed from the draw's own screen, not here.
        </p>
    </div>

    <form method="POST" action="{{ route('admin.events.update', $event) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('admin.events._form')
    </form>
</div>
@endsection
