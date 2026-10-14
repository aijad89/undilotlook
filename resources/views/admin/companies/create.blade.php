@extends('layouts.admin')

@section('title', 'New Company')

@section('content')
@include('partials.mac-styles')

<div class="mac-page">
    @include('partials.admin-alerts')

    <div class="mac-breadcrumb mb-2">
        <a href="{{ route('admin.companies.index') }}">Companies</a> / New
    </div>

    <div class="mac-header mb-4">
        <h2>New Company</h2>
        <p class="mac-muted mb-0">
            The company and its account are created together. Craft categories are
            picked on the next screen, once the company exists.
        </p>
    </div>

    <form method="POST" action="{{ route('admin.companies.store') }}">
        @csrf
        @include('admin.companies._form')
    </form>
</div>
@endsection
