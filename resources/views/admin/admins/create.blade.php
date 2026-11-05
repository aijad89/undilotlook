@extends('layouts.admin')

@php $listTitle = $group === 'hq' ? 'HQ Admins' : 'State Admins'; @endphp

@section('title', 'New Admin')

@section('content')
@include('partials.mac-styles')

<div class="mac-page">
    @include('partials.admin-alerts')

    <div class="mac-breadcrumb mb-2">
        <a href="{{ route('admin.admins.'.$group) }}">{{ $listTitle }}</a> <span class="sep" aria-hidden="true">›</span> New
    </div>

    <div class="mac-header mb-4">
        <h2>New Admin</h2>
        <p class="mac-muted mb-0">An account for the admin panel. They sign in with this email and password.</p>
    </div>

    <form method="POST" action="{{ route('admin.admins.store') }}">
        @csrf
        @include('admin.admins._form')
    </form>
</div>
@endsection
