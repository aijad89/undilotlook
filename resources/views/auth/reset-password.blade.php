{{-- The chrome-less auth layout, like auth/login. A guest has no sidebar
     to show, and pulling the whole portal layout into the reset flow makes
     every guest page depend on every portal route name resolving. --}}
@extends('layouts.user-auth')

@section('title', 'Reset Password')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white text-center py-3">
                    <h2 class="mb-0" style="font-size:1.5rem;">Reset Password</h2>
                </div>
                <div class="card-body">

                    <p class="text-muted mb-4">
                        Choose a new password. Any other device signed in to your account is signed out.
                    </p>

                    {{-- password.store = POST /reset-password, the guest route.
                         This form used to point at password.update — PUT-only and
                         behind auth — so it rendered clean and 405'd on submit.
                         That route is deleted now, so the name no longer resolves
                         at all; see the comment where it lived in routes/auth.php. --}}
                    <form method="POST" action="{{ route('password.store') }}">
                        @csrf

                        <!-- Hidden Token -->
                        <input type="hidden" name="token" value="{{ $token }}">

                        <!-- Email -->
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                class="form-control @error('email') is-invalid @enderror"
                                value="{{ old('email', request()->email) }}"
                                required
                                readonly
                            >
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- New Password -->
                        <div class="mb-3">
                            <label for="password" class="form-label">New Password</label>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="form-control @error('password') is-invalid @enderror"
                                required
                            >
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Confirm Password -->
                        <div class="mb-4">
                            <label for="password_confirmation" class="form-label">Confirm Password</label>
                            <input
                                type="password"
                                id="password_confirmation"
                                name="password_confirmation"
                                class="form-control"
                                required
                            >
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                Reset Password
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
