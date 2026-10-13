<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin Login')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        * { box-sizing: border-box; }
        html, body { height: 100%; margin: 0; padding: 0; }

        .login-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* Left panel */
        .login-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 3rem 2rem;
            color: #fff;
            text-align: center;
            position: relative;
            background: linear-gradient(160deg, #0d47a1, #0a58ca, #06b6d4);
        }
        .login-panel.has-bg {
            background-image: var(--login-bg);
            background-size: cover;
            background-position: center;
        }
        .login-panel-overlay {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.40);
        }
        .login-panel-content {
            position: relative;
            z-index: 1;
        }
        .login-panel-title {
            font-size: 2.4rem;
            font-weight: 700;
            letter-spacing: 1px;
            line-height: 1.2;
        }
        .login-panel-subtitle {
            font-size: 1rem;
            opacity: 0.85;
            margin-top: 0.5rem;
        }

        /* Right panel */
        .login-form-panel {
            width: 420px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 3rem 2.5rem;
            background: #fff;
        }

        /* Responsive: stack on small screens */
        @media (max-width: 768px) {
            .login-wrapper { flex-direction: column; }
            .login-panel { min-height: 200px; flex: none; padding: 2rem 1rem; }
            .login-panel-title { font-size: 1.6rem; }
            .login-form-panel { width: 100%; padding: 2rem 1.5rem; }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        @php
            $loginBg    = \App\Models\Setting::get('admin_login_bg_image');
            $loginTitle = \App\Models\Setting::get('admin_login_title', 'undilot Admin');
            $loginSub   = \App\Models\Setting::get('admin_login_subtitle', 'Sistem Cabutan Lot');
        @endphp

        <!-- Left branding panel -->
        <div class="login-panel {{ $loginBg ? 'has-bg' : '' }}"
             @if($loginBg) style="--login-bg: url('{{ asset('storage/' . $loginBg) }}')" @endif>
            <div class="login-panel-overlay"></div>
            <div class="login-panel-content">
                <div class="login-panel-title">{{ $loginTitle }}</div>
                <div class="login-panel-subtitle">{{ $loginSub }}</div>
            </div>
        </div>

        <!-- Right form panel -->
        <div class="login-form-panel">
            @yield('content')
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
