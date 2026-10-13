{{-- The chrome-less auth layout, like auth/login. A guest has no sidebar
     to show, and pulling the whole portal layout into the reset flow makes
     every guest page depend on every portal route name resolving. --}}
@extends('layouts.user-auth')

@section('title', 'Forgot Password')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white text-center py-3">
                    <h2 class="mb-0" style="font-size:1.5rem;">Forgot Password?</h2>
                </div>
                <div class="card-body">

                    <p class="text-muted mb-4">
                        No problem. Just enter your email address and we'll send you a link to reset your password.
                    </p>

                    <!-- Session Status -->
                    @if (session('status'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('status') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.email') }}">
                        @csrf

                        <div class="mb-4">
                            <label for="email" class="form-label">Email Address</label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                class="form-control @error('email') is-invalid @enderror"
                                value="{{ old('email') }}"
                                required
                                autofocus
                            >
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                Email Password Reset Link
                            </button>
                        </div>

                        <div class="text-center mt-3">
                            <a href="{{ route('login') }}" class="text-decoration-none small">
                                ← Back to Login
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
