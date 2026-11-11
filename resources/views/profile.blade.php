@extends('layouts.frontend')

@section('title', 'Profile')
@section('page-title', 'Profile')

@section('content')
@include('partials.apple-styles')

@php
    // The IC number, all but its last four digits hidden; the dashes stay.
    $ic = $user->ic_number ? preg_replace('/\d(?=(?:\D*\d){4})/', '•', $user->ic_number) : null;
    $pw = $errors->password;
@endphp

<div class="ap-page ap-dash">
    <div class="ap-page-header">
        <div>
            <h2 class="ap-page-title">Profile</h2>
            <p class="ap-page-subtitle">Your account and company, as the Kraftangan office keeps them.</p>
        </div>
    </div>

    <div class="ap-dash-grid ap-profile-grid">
        <div class="ap-dash-side">
            <section class="ap-card ap-card-padded">
                <h2 class="ap-section-title">Your account</h2>
                <dl class="ap-facts">
                    <dt>Name</dt>
                    <dd>{{ $user->name }}</dd>
                    <dt>Email, to sign in</dt>
                    <dd>{{ $user->email }}</dd>
                    <dt>Mobile phone</dt>
                    <dd class="ap-tabular">{{ $user->phone ?: '—' }}</dd>
                    <dt>IC number</dt>
                    <dd class="ap-tabular">{{ $ic ?: '—' }}</dd>
                </dl>
            </section>

            <section class="ap-card ap-card-padded">
                <h2 class="ap-section-title">Your company</h2>
                @if($company)
                    <dl class="ap-facts">
                        <dt>Company</dt>
                        <dd>{{ $company->name }}</dd>
                        <dt>Zone</dt>
                        <dd>{{ $company->state ?: '—' }}</dd>
                        <dt>Usual booth size</dt>
                        <dd class="ap-tabular">{{ $company->plot_size ?: '3x3' }}</dd>
                    </dl>
                @else
                    <p class="ap-side-note">Your account has no company yet.</p>
                @endif
                <p class="ap-side-note mb-0">The office keeps these details. If any is wrong, contact the Kraftangan office to change it.</p>
            </section>

        </div>

        <div class="ap-dash-side">
            <section class="ap-card ap-card-padded" aria-labelledby="pw-title">
                <h2 class="ap-section-title" id="pw-title">Change password</h2>
                @if(session('success'))
                    <div class="ap-alert ap-alert-success" role="status">
                        <i class="bi bi-check-circle ap-alert-icon" aria-hidden="true"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif
                <form method="POST" action="{{ route('profile.password') }}" class="ap-form" novalidate>
                    @csrf
                    @method('PUT')
                    @foreach([
                        ['current_password', 'Current password', 'current-password', null],
                        ['password', 'New password', 'new-password', 'At least 8 characters, with letters and numbers.'],
                        ['password_confirmation', 'New password again', 'new-password', null],
                    ] as [$field, $label, $auto, $hint])
                        <div class="ap-field">
                            <label class="ap-label" for="{{ $field }}">{{ $label }}</label>
                            <input type="password" class="ap-input" id="{{ $field }}" name="{{ $field }}" autocomplete="{{ $auto }}" required
                                   @if($pw->has($field)) aria-invalid="true" aria-describedby="{{ $field }}-error" @elseif($hint) aria-describedby="{{ $field }}-hint" @endif>
                            @if($pw->has($field))
                                <p class="ap-field-error" id="{{ $field }}-error">{{ $pw->first($field) }}</p>
                            @elseif($hint)
                                <p class="ap-field-hint" id="{{ $field }}-hint">{{ $hint }}</p>
                            @endif
                        </div>
                    @endforeach
                    <button type="submit" class="ap-btn ap-btn-primary">Change password</button>
                </form>
            </section>
        </div>

    </div>
</div>
@endsection
