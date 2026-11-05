@extends('layouts.admin')
@section('title', 'New Social Link')

@section('content')
@include('partials.mac-styles')

<div class="mac-page">

    <div class="mac-header mb-4 d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h2>New Social Link</h2>
            <div class="mac-breadcrumb">
                <a href="{{ route('admin.dashboard') }}">Dashboard</a>
                <span class="sep" aria-hidden="true">›</span>
                <a href="{{ route('admin.social-links.index') }}">Social Links</a>
                <span class="sep" aria-hidden="true">›</span>
                New
            </div>
            <p class="mac-muted mb-0">An icon in the site footer that links to one of the office's pages.</p>
        </div>
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
