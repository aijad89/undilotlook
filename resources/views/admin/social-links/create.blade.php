@extends('layouts.admin')
@section('title', 'New Social Link')

@section('content')
@include('partials.mac-styles')

<div class="mac-page">

    <div class="d-flex justify-content-between align-items-start mb-4">
        <div class="mac-header">
            <h2>New Social Link</h2>
            <div class="mac-breadcrumb">
                <a href="{{ route('admin.dashboard') }}">Dashboard</a>
                <span class="sep" aria-hidden="true">›</span>
                <a href="{{ route('admin.social-links.index') }}">Social Links</a>
                <span class="sep" aria-hidden="true">›</span>
                New
            </div>
        </div>
        <a href="{{ route('admin.social-links.index') }}" class="mac-btn-secondary">← Back</a>
    </div>

    @include('partials.admin-alerts')

    <form method="POST" action="{{ route('admin.social-links.store') }}" enctype="multipart/form-data">
        @csrf
        @include('admin.social-links._form', ['link' => null])
    </form>

</div>
@endsection

@push('scripts')
@include('admin.social-links._scripts')
@endpush
