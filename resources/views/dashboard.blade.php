@extends('layouts.frontend')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
@include('partials.apple-styles')

<div class="ap-card ap-card-padded">
    <h2 style="font-size:1.1rem;font-weight:600;margin-bottom:6px;">
        Selamat datang, {{ $user->name }}
    </h2>
    <p class="text-muted mb-0">
        Acara yang anda masuk cabutan akan muncul di sini, bersama lot yang anda
        tarik. Belum ada apa-apa buat masa ini.
    </p>
</div>
@endsection
