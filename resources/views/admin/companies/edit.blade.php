@extends('layouts.admin')

@section('title', 'Edit Company')

@section('content')
@include('partials.mac-styles')

<div class="mac-page">
    @include('partials.admin-alerts')

    <div class="mac-breadcrumb mb-2">
        <a href="{{ route('admin.companies.index') }}">Companies</a> / {{ $company->name }}
    </div>

    <div class="mac-header mb-4">
        <h2>{{ $company->name }}</h2>
        <p class="mac-muted mb-0">{{ $company->state ?? 'No zone' }}</p>
    </div>

    <form method="POST" action="{{ route('admin.companies.update', $company) }}">
        @csrf
        @method('PUT')
        @include('admin.companies._form')
    </form>

    {{-- Outside the form above, deliberately: the picker posts to its own route,
         and a form inside a form is not a thing a browser will send. --}}
    @include('admin.companies.partials.kraf-section')
</div>
@endsection
