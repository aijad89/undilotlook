<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Login')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        * { box-sizing: border-box; }
        html, body { height: 100%; margin: 0; padding: 0; }

        .login-wrapper {
            display: flex;
            min-height: 100vh;
        }

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
            background: linear-gradient(160deg, #1a3a6b, #2563eb, #0891b2);
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

        .login-form-panel {
            width: 420px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 3rem 2.5rem;
            background: #fff;
        }

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
            $loginBg    = \App\Models\Setting::get('user_login_bg_image');
            $loginTitle = \App\Models\Setting::get('user_login_title', 'undilot');
            $loginSub   = \App\Models\Setting::get('user_login_subtitle', 'Portal Cabutan Lot Usahawan Kraf');
        @endphp

        <header class="login-panel {{ $loginBg ? 'has-bg' : '' }}"
             @if($loginBg) style="--login-bg: url('{{ asset('storage/' . $loginBg) }}')" @endif>
            <div class="login-panel-overlay"></div>
            <div class="login-panel-content">
                <div class="login-panel-logo mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="72" height="72" viewBox="0 0 72 72" fill="none">
                        <rect width="72" height="72" rx="16" fill="rgba(255,255,255,0.15)"/>
                        <!-- Document stack -->
                        <rect x="18" y="24" width="28" height="34" rx="3" fill="rgba(255,255,255,0.25)" stroke="rgba(255,255,255,0.6)" stroke-width="1.5"/>
                        <rect x="22" y="20" width="28" height="34" rx="3" fill="rgba(255,255,255,0.35)" stroke="rgba(255,255,255,0.7)" stroke-width="1.5"/>
                        <rect x="26" y="16" width="28" height="34" rx="3" fill="white" stroke="rgba(255,255,255,0.9)" stroke-width="1.5"/>
                        <!-- Lines on top document -->
                        <rect x="31" y="24" width="16" height="2.5" rx="1.25" fill="#2563eb" opacity="0.7"/>
                        <rect x="31" y="29" width="12" height="2" rx="1" fill="#2563eb" opacity="0.5"/>
                        <rect x="31" y="33" width="14" height="2" rx="1" fill="#2563eb" opacity="0.5"/>
                        <!-- Check badge -->
                        <circle cx="54" cy="52" r="10" fill="#22c55e"/>
                        <path d="M49 52l3.5 3.5 6-6" stroke="white" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
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
