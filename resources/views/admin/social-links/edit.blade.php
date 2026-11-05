@extends('layouts.admin')
@section('title', 'Edit Social Link')

@section('content')
@include('partials.mac-styles')

<div class="mac-page">

    <div class="mac-header mb-4 d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h2>Edit Social Link</h2>
            <div class="mac-breadcrumb">
                <a href="{{ route('admin.dashboard') }}">Dashboard</a>
                <span class="sep" aria-hidden="true">›</span>
                <a href="{{ route('admin.social-links.index') }}">Social Links</a>
                <span class="sep" aria-hidden="true">›</span>
                Edit
            </div>
            <p class="mac-muted mb-0">Changes show in the footer as soon as they are saved.</p>
        </div>
    </div>

    @include('partials.admin-alerts')

    <form method="POST" action="{{ route('admin.social-links.update', $socialLink) }}"
          enctype="multipart/form-data">
        @csrf @method('PUT')
        @include('admin.social-links._form', ['link' => $socialLink])
    </form>

</div>
@endsection

@push('scripts')
@include('admin.social-links._scripts')
@endpush
