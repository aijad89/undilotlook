@extends('layouts.admin')

@section('title', 'New Event')

@section('content')
@include('partials.mac-styles')

<div class="mac-page">
    @include('partials.admin-alerts')

    <div class="mac-breadcrumb mb-2">
        <a href="{{ route('admin.events.index') }}">Events</a> <span class="sep" aria-hidden="true">›</span> New
    </div>

    <div class="mac-header mb-4">
        <h2>New Event</h2>
        <p class="mac-muted mb-0">The draw inside it is set up afterwards, on its own screen.</p>
    </div>

    <form method="POST" action="{{ route('admin.events.store') }}" enctype="multipart/form-data">
        @csrf
        @include('admin.events._form')
    </form>
</div>
@endsection
