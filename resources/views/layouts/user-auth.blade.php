<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Login')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"
          integrity="sha384-XGjxtQfXaH2tnPFa9x+ruJTuLE3Aa6LhHSWRr1XeTyhezb4abCG4ccI5AkVDxqC+" crossorigin="anonymous">
    @include('partials.ui-tokens')
    @include('partials.auth-styles')
</head>
<body>
    <div class="login-wrapper">
        @php
            $loginBg    = \App\Models\Setting::get('user_login_bg_image');
            $loginTitle = \App\Models\Setting::get('user_login_title', 'undilot');
            $loginSub   = \App\Models\Setting::get('user_login_subtitle', 'Portal Cabutan Lot Usahawan Kraf');
        @endphp

        <header class="login-panel {{ $loginBg ? 'has-bg' : '' }}"
             @if($loginBg) style="--login-bg: url('{{ asset('storage/' . $loginBg) }}')" @endif>
            <div class="login-panel-overlay"></div>
            <div class="login-panel-content">
                <div class="login-mark" aria-hidden="true"><i class="bi bi-grid-3x3-gap-fill"></i></div>
                <div class="login-panel-title">{{ $loginTitle }}</div>
                <div class="login-panel-subtitle">{{ $loginSub }}</div>
            </div>
        </header>

        <main class="login-form-panel">
            <h1 class="visually-hidden">@yield('title', 'Login')</h1>
            @yield('content')
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
