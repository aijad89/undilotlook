@extends('layouts.admin')

@php $listTitle = $group === 'hq' ? 'HQ Admins' : 'State Admins'; @endphp

@section('title', 'Edit Admin')

@section('content')
@include('partials.mac-styles')

<div class="mac-page">
    @include('partials.admin-alerts')

    <div class="mac-breadcrumb mb-2">
        <a href="{{ route('admin.admins.'.$group) }}">{{ $listTitle }}</a> <span class="sep" aria-hidden="true">›</span> {{ $admin->name }}
    </div>

    <div class="mac-header mb-4">
        <h2>{{ $admin->name }}</h2>
        <p class="mac-muted mb-0">{{ \App\Models\Admin::ROLES[$admin->role] ?? $admin->role }}{{ $admin->state ? ' · '.$admin->state : '' }}</p>
    </div>

    <form method="POST" action="{{ route('admin.admins.update', $admin) }}">
        @csrf
        @method('PUT')
        @include('admin.admins._form')
    </form>
</div>
@endsection
