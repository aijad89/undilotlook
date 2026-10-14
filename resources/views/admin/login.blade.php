@extends('layouts.backend')

@section('title', 'Admin Login')

@section('content')
<div style="width: 100%;">
    <div class="mb-4">
        <h1 class="h3 fw-bold mb-1">Welcome back</h1>
        <p class="text-muted mb-0" style="font-size: 0.9rem;">Sign in to your admin account</p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ $errors->first() }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.login') }}">
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label fw-semibold">Email</label>
            <input type="email"
                   class="form-control form-control-lg @error('email') is-invalid @enderror"
                   id="email"
                   name="email"
                   value="{{ old('email') }}"
                   placeholder="admin@example.com"
                   required autofocus>
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="password" class="form-label fw-semibold">Password</label>
            <input type="password"
                   class="form-control form-control-lg @error('password') is-invalid @enderror"
                   id="password"
                   name="password"
                   placeholder="••••••••"
                   required>
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-4 form-check">
            <input type="checkbox" class="form-check-input" id="remember" name="remember">
            <label class="form-check-label text-muted" for="remember">Remember me</label>
        </div>

        <div class="d-grid">
            <button type="submit" class="btn btn-primary btn-lg">Sign In</button>
        </div>
    </form>
</div>
@endsection
