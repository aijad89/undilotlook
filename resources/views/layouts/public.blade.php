<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', \App\Models\Setting::get('site_name', 'undilot'))</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"
          integrity="sha384-XGjxtQfXaH2tnPFa9x+ruJTuLE3Aa6LhHSWRr1XeTyhezb4abCG4ccI5AkVDxqC+" crossorigin="anonymous">
    <style>
        :root {
            --pub-blue:     #007AFF;
            --pub-blue-hov: #0066DD;
            --pub-green:    #34C759;
            --pub-red:      #FF3B30;
            --pub-orange:   #FF9500;
            --pub-purple:   #AF52DE;
            --pub-bg:       #F2F2F7;
            --pub-text:     #1C1C1E;
            --pub-text2:    #3A3A3C;
            --pub-muted:    #6E6E73;
            --pub-border:   rgba(60,60,67,0.12);
            --pub-nav-bg:   rgba(255,255,255,0.88);
            --pub-shadow:   0 1px 3px rgba(0,0,0,0.06), 0 4px 16px rgba(0,0,0,0.05);
            --pub-font:     -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'SF Pro Text', 'Helvetica Neue', Arial, sans-serif;
            --pub-radius:   14px;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html, body {
            font-family: var(--pub-font);
            background: var(--pub-bg);
            color: var(--pub-text);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            overflow-x: hidden;
        }

        /* ── Navbar ─────────────────────────────── */
        .pub-nav {
            position: sticky;
            top: 0;
            z-index: 1000;
            height: 52px;
            background: var(--pub-nav-bg);
            backdrop-filter: blur(24px) saturate(180%);
            -webkit-backdrop-filter: blur(24px) saturate(180%);
            border-bottom: 1px solid var(--pub-border);
        }

        .pub-nav-inner {
            max-width: 1100px;
            margin: 0 auto;
            height: 100%;
            display: flex;
            align-items: center;
            padding: 0 2rem;
            gap: 0.5rem;
        }

        .pub-brand {
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            flex-shrink: 0;
        }

        .pub-brand-icon {
            width: 28px;
            height: 28px;
            background: var(--pub-blue);
            border-radius: 7px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 2px 6px rgba(0,122,255,0.35);
        }

        .pub-brand-icon svg { width: 14px; height: 14px; }

        .pub-brand-name {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--pub-text);
            letter-spacing: -0.02em;
        }

        .pub-nav-links {
            display: flex;
            align-items: center;
            margin-left: 1.5rem;
            flex: 1;
        }

        .pub-nav-links a {
            font-size: 0.845rem;
            font-weight: 500;
            color: var(--pub-text2);
            text-decoration: none;
            padding: 0 0.75rem;
            height: 52px;
            display: flex;
            align-items: center;
            border-bottom: 2px solid transparent;
            transition: color 0.12s, border-color 0.12s;
        }

        .pub-nav-links a:hover { color: var(--pub-blue-hov); }
        .pub-nav-links a.active {
            color: var(--pub-blue-hov);
            border-bottom-color: var(--pub-blue);
        }

        .pub-nav-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-left: auto;
        }

        .pub-btn-ghost {
            font-size: 0.82rem;
            font-weight: 500;
            color: var(--pub-text2);
            text-decoration: none;
            padding: 6px 14px;
            border-radius: 980px;
            border: 1px solid var(--pub-border);
            transition: all 0.12s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: transparent;
        }

        .pub-btn-ghost:hover {
            background: rgba(60,60,67,0.07);
            color: var(--pub-text);
        }

        .pub-btn-primary {
            font-size: 0.82rem;
            font-weight: 600;
            color: #fff;
            text-decoration: none;
            padding: 6px 16px;
            border-radius: 980px;
            background: var(--pub-blue-hov);
            transition: background 0.12s, transform 0.1s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            border: none;
            cursor: pointer;
        }

        .pub-btn-primary:hover { background: #0052B4; color: #fff; transform: scale(1.02); }

        .pub-user-btn {
            display: flex;
            align-items: center;
            gap: 7px;
            padding: 4px 12px 4px 4px;
            border-radius: 980px;
            text-decoration: none;
            background: transparent;
            border: 1px solid var(--pub-border);
            transition: background 0.12s;
            color: var(--pub-text);
            font-size: 0.82rem;
            font-weight: 500;
            cursor: pointer;
        }

        .pub-user-btn:hover { background: rgba(60,60,67,0.06); color: var(--pub-text); }

        .pub-avatar {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--pub-blue), #5856D6);
            color: #fff;
            font-size: 0.67rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .pub-dropdown {
            border: 1px solid var(--pub-border);
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.1);
            padding: 6px;
            min-width: 180px;
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            background: rgba(255,255,255,0.96);
        }

        .pub-dropdown .dropdown-item {
            border-radius: 8px;
            font-size: 0.84rem;
            padding: 7px 12px;
            color: var(--pub-text);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .pub-dropdown .dropdown-item:hover { background: rgba(0,122,255,0.08); color: var(--pub-blue-hov); }
        .pub-dropdown .dropdown-item.text-danger:hover { background: rgba(255,59,48,0.08); color: #B91C1C; }
        .pub-dropdown hr { border-color: rgba(60,60,67,0.08); margin: 4px 0; }

        /* ── Footer ─────────────────────────────── */
        .pub-footer-wrap { width: 100%; }

        /* Content panel */
        .pub-footer-panel {
            display: none;
            background: rgba(255,255,255,0.97);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-top: 1px solid var(--pub-border);
            border-bottom: 1px solid var(--pub-border);
            padding: 28px 2rem;
            animation: pubPanelIn 0.22s ease;
        }

        .pub-footer-panel.show { display: block; }

        @keyframes pubPanelIn {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .pub-footer-panel-inner {
            max-width: 1100px;
            margin: 0 auto;
        }

        .pub-footer-panel-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--pub-text);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .pub-footer-panel-close {
            background: none;
            border: none;
            cursor: pointer;
            color: var(--pub-muted);
            font-size: 1.1rem;
            line-height: 1;
            padding: 2px 6px;
            border-radius: 6px;
            transition: background 0.12s, color 0.12s;
        }

        .pub-footer-panel-close:hover {
            background: rgba(0,0,0,0.06);
            color: var(--pub-text);
        }

        .pub-footer-panel-body {
            font-size: 0.85rem;
            line-height: 1.7;
            color: #3A3A3C;
            max-height: 220px;
            overflow-y: auto;
        }

        .pub-footer-panel-body a { color: var(--pub-blue-hov); text-decoration: underline; }

        /* Footer bar */
        .pub-footer {
            background: rgba(255,255,255,0.9);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-top: 1px solid var(--pub-border);
            padding: 20px 2rem 16px;
        }

        .pub-footer-inner {
            max-width: 1100px;
            margin: 0 auto;
        }

        .pub-footer-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 14px;
        }

        .pub-footer-brand {
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            color: var(--pub-text);
            font-size: 0.87rem;
            font-weight: 700;
        }

        .pub-footer-icon {
            width: 24px;
            height: 24px;
            background: var(--pub-blue);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .pub-footer-icon svg { width: 13px; height: 13px; }

        .pub-footer-copy {
            font-size: 0.78rem;
            color: var(--pub-muted);
        }

        /* Footer bottom row */
        .pub-footer-bottom {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 24px;
        }

        /* Pyramid menu — plain text, left-aligned */
        .pub-footer-menu-rows { flex: 1; }

        .pub-footer-menu-row {
            display: flex;
            justify-content: flex-start;
            gap: 20px;
            margin-bottom: 4px;
            flex-wrap: wrap;
        }

        .pub-footer-menu-row:last-child { margin-bottom: 0; }

        .pub-footer-menu-btn {
            background: none;
            border: none;
            padding: 0;
            font-size: 0.8rem;
            color: var(--pub-muted);
            cursor: pointer;
            transition: color 0.15s;
            white-space: nowrap;
        }

        .pub-footer-menu-btn:hover,
        .pub-footer-menu-btn.active {
            background: none;
            border: none;
            color: var(--pub-blue-hov);
        }

        /* Follow Us */
        .pub-footer-follow { text-align: right; flex-shrink: 0; }

        .pub-footer-follow-label {
            font-size: 0.72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--pub-muted);
            margin-bottom: 8px;
        }

        .pub-footer-follow-icons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .pub-footer-follow-icons a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: rgba(0,0,0,0.05);
            color: var(--pub-muted);
            font-size: 1rem;
            text-decoration: none;
            transition: background 0.15s, color 0.15s;
        }

        .pub-footer-follow-icons a:hover {
            background: var(--pub-blue);
            color: #fff;
        }

        @media (max-width: 576px) {
            .pub-footer-bottom { flex-direction: column; }
            .pub-footer-follow { text-align: left; }
            .pub-footer-follow-icons { justify-content: flex-start; }
        }

        @media (max-width: 768px) {
            .pub-nav-inner { padding: 0 1rem; }
            .pub-nav-links { display: none; }
            .pub-footer-top { flex-direction: column; text-align: center; }
        }
    </style>
    @stack('styles')
</head>
<body>
@include('partials.skip-link')

@php $siteName = \App\Models\Setting::get('site_name', 'undilot'); @endphp

{{-- ── Navbar ──────────────────────────────────────────── --}}
<nav class="pub-nav">
    <div class="pub-nav-inner">
        <a href="{{ route('home') }}" class="pub-brand">
            <div class="pub-brand-icon">
                <svg viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect x="1.5" y="3" width="9" height="11" rx="1.8" fill="rgba(255,255,255,0.3)" stroke="rgba(255,255,255,0.7)" stroke-width="1.1"/>
                    <rect x="4.5" y="1.5" width="9" height="11" rx="1.8" fill="white"/>
                    <rect x="6.5" y="4.5" width="5" height="1.1" rx="0.55" fill="#007AFF"/>
                    <rect x="6.5" y="6.5" width="3.5" height="1.1" rx="0.55" fill="#007AFF" opacity="0.55"/>
                    <rect x="6.5" y="8.5" width="4" height="1.1" rx="0.55" fill="#007AFF" opacity="0.55"/>
                </svg>
            </div>
            <span class="pub-brand-name">{{ $siteName }}</span>
        </a>

        <div class="pub-nav-links">
            <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">Events</a>
            @auth
                <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">Dashboard</a>
            @endauth
        </div>

        <div class="pub-nav-actions">
            @guest
                <a href="{{ route('login') }}" class="pub-btn-primary">Sign In</a>
            @endguest

            @auth
                <div class="dropdown">
                    <button class="pub-user-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="border:none;">
                        <div class="pub-avatar">{{ strtoupper(substr(Auth::user()->name, 0, 2)) }}</div>
                        <span class="d-none d-sm-inline" style="max-width:110px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ Auth::user()->name }}</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end pub-dropdown">
                        <li><a class="dropdown-item" href="{{ route('dashboard') }}"><i class="bi bi-house-door" style="color:#5856D6"></i> Dashboard</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger" style="width:100%;text-align:left;">
                                    <i class="bi bi-box-arrow-right"></i> Sign Out
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            @endauth
        </div>
    </div>
</nav>

<main id="main-content">
{{-- Fallback heading for pages with no visible h1. Pages that render their own
     declare @section('own-heading') so this does not produce a second h1. --}}
@hasSection('own-heading')
@else
    <h1 class="visually-hidden">@yield('title', \App\Models\Setting::get('site_name', 'undilot'))</h1>
@endif
@yield('content')
</main>

{{-- ── Footer ──────────────────────────────────────────── --}}
@php
    $pubFooterMenus = \App\Models\FooterMenu::active()->orderBy('sort_order')->orderBy('id')->get();
    $pubFooterRows  = [];
    $pfI            = 0;
    $pfPattern      = [5, 4];
    $pfPIdx         = 0;
    while ($pfI < $pubFooterMenus->count()) {
        $pfSize = $pfPattern[$pfPIdx % 2];
        $pubFooterRows[] = $pubFooterMenus->slice($pfI, $pfSize)->values();
        $pfI += $pfSize;
        $pfPIdx++;
    }
@endphp

<div class="pub-footer-wrap">
    {{-- Content panel --}}
    <div class="pub-footer-panel" id="pubFooterPanel">
        <div class="pub-footer-panel-inner">
            <div class="pub-footer-panel-title">
                <span id="pubFooterPanelTitle"></span>
                <button class="pub-footer-panel-close" onclick="closePubFooterPanel()" title="Close">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="pub-footer-panel-body" id="pubFooterPanelBody"></div>
        </div>
    </div>

    {{-- Footer bar --}}
    <footer class="pub-footer">
        <div class="pub-footer-inner">
            <div class="pub-footer-top">
                <a href="{{ route('home') }}" class="pub-footer-brand">
                    <div class="pub-footer-icon">
                        <svg viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect x="1.5" y="3" width="9" height="11" rx="1.8" fill="rgba(255,255,255,0.3)" stroke="rgba(255,255,255,0.7)" stroke-width="1.1"/>
                            <rect x="4.5" y="1.5" width="9" height="11" rx="1.8" fill="white"/>
                            <rect x="6.5" y="4.5" width="5" height="1.1" rx="0.55" fill="#007AFF"/>
                            <rect x="6.5" y="6.5" width="3.5" height="1.1" rx="0.55" fill="#007AFF" opacity="0.55"/>
                            <rect x="6.5" y="8.5" width="4" height="1.1" rx="0.55" fill="#007AFF" opacity="0.55"/>
                        </svg>
                    </div>
                    {{ $siteName }}
                </a>
                <div class="pub-footer-copy">&copy; {{ date('Y') }} {{ $siteName }}. All Rights Reserved.</div>
            </div>

            <div class="pub-footer-bottom">
                {{-- Menu links — left --}}
                @if($pubFooterMenus->count() > 0)
                    <nav class="pub-footer-menu-rows" aria-label="Footer menu">
                        @foreach($pubFooterRows as $row)
                            <div class="pub-footer-menu-row">
                                @foreach($row as $pfItem)
                                    <button class="pub-footer-menu-btn"
                                            data-id="{{ $pfItem->id }}"
                                            data-title="{{ e($pfItem->title) }}"
                                            data-content="{{ e($pfItem->content) }}"
                                            onclick="togglePubFooterPanel(this)">
                                        {{ $pfItem->title }}
                                    </button>
                                @endforeach
                            </div>
                        @endforeach
                    </nav>
                @else
                    <div></div>
                @endif

                {{-- Follow Us — right --}}
                @php $pubSocialLinks = \App\Models\SocialLink::active()->orderBy('sort_order')->orderBy('id')->get(); @endphp
                @if($pubSocialLinks->count() > 0)
                    <div class="pub-footer-follow">
                        <div class="pub-footer-follow-icons">
                            @foreach($pubSocialLinks as $sl)
                                <a href="{{ $sl->url }}" target="_blank" rel="noopener" title="{{ $sl->label }}">
                                    @if($sl->icon_type === 'image' && $sl->icon_image)
                                        <img src="{{ $sl->icon_image_url }}" width="16" height="24" loading="lazy"
                                             style="object-fit:contain;" alt="{{ $sl->label }}">
                                    @else
                                        <i class="bi {{ $sl->icon_class ?: 'bi-globe' }}"></i>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </footer>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
@stack('scripts')
<script>
    let activePubFooterBtn = null;

    function togglePubFooterPanel(btn) {
        const panel = document.getElementById('pubFooterPanel');

        if (activePubFooterBtn === btn && panel.classList.contains('show')) {
            closePubFooterPanel();
            return;
        }

        document.querySelectorAll('.pub-footer-menu-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        activePubFooterBtn = btn;

        document.getElementById('pubFooterPanelTitle').textContent = btn.dataset.title;
        document.getElementById('pubFooterPanelBody').innerHTML    = btn.dataset.content;
        panel.classList.add('show');
        panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function closePubFooterPanel() {
        document.getElementById('pubFooterPanel').classList.remove('show');
        document.querySelectorAll('.pub-footer-menu-btn').forEach(b => b.classList.remove('active'));
        activePubFooterBtn = null;
    }
</script>
</body>
</html>
