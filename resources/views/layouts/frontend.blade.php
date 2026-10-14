<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'undilot')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
          integrity="sha384-XGjxtQfXaH2tnPFa9x+ruJTuLE3Aa6LhHSWRr1XeTyhezb4abCG4ccI5AkVDxqC+" crossorigin="anonymous">

    {{-- Apply enlarged-text class before paint to avoid flash --}}
    <script>(function(){var s=localStorage.getItem('textSizeLevel');if(s==='1')document.documentElement.classList.add('text-large');else if(s==='2')document.documentElement.classList.add('text-xlarge');})();</script>

    <style>
        /* ──────────────────────────────────────────
           Apple UI Design System — User Portal
        ────────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --apple-blue:     #007AFF;
            --apple-blue-hov: #0066DD;
            --apple-green:    #34C759;
            --apple-red:      #FF3B30;
            --apple-orange:   #FF9500;
            --apple-purple:   #AF52DE;
            --apple-teal:     #5AC8FA;
            --apple-bg:       #F2F2F7;
            --apple-bg2:      #FFFFFF;
            --apple-card:     #FFFFFF;
            --apple-sidebar:  rgba(246, 246, 248, 0.92);
            --apple-topbar:   rgba(255, 255, 255, 0.85);
            --apple-text:     #1C1C1E;
            --apple-text2:    #3A3A3C;
            --apple-muted:    #6E6E73;
            --apple-border:   rgba(60, 60, 67, 0.12);
            --apple-sep:      rgba(60, 60, 67, 0.08);
            --apple-shadow:   0 1px 3px rgba(0,0,0,0.06), 0 4px 16px rgba(0,0,0,0.06);
            --apple-shadow-lg:0 2px 8px rgba(0,0,0,0.06), 0 12px 40px rgba(0,0,0,0.08);
            --apple-radius:   12px;
            --apple-radius-sm:8px;
            --apple-font:     -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'SF Pro Text', 'Helvetica Neue', Arial, sans-serif;
            --sidebar-w:      242px;
            --topbar-h:       52px;
        }

        html, body {
            height: 100%;
            background: var(--apple-bg);
            font-family: var(--apple-font);
            color: var(--apple-text);
            font-size: 0.9rem;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            overflow-x: hidden;
        }

        /* ── Sidebar ─────────────────────────────── */
        .ap-sidebar {
            position: fixed;
            inset: 0 auto 0 0;
            width: var(--sidebar-w);
            background: var(--apple-sidebar);
            backdrop-filter: blur(28px) saturate(180%);
            -webkit-backdrop-filter: blur(28px) saturate(180%);
            border-right: 1px solid var(--apple-border);
            z-index: 1000;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .ap-sidebar-header {
            padding: 18px 16px 14px;
            border-bottom: 1px solid var(--apple-sep);
            flex-shrink: 0;
        }

        .ap-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .ap-brand-icon {
            width: 30px;
            height: 30px;
            background: var(--apple-blue);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(0,122,255,0.35);
        }

        .ap-brand-icon svg { width: 16px; height: 16px; }

        .ap-brand-name {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--apple-text);
            letter-spacing: -0.02em;
        }

        /* Sidebar section labels */
        .ap-sidebar-label {
            font-size: 0.67rem;
            font-weight: 600;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--apple-muted);
            padding: 16px 16px 4px;
        }

        /* Sidebar nav items */
        .ap-nav-item {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 7px 12px 7px 14px;
            margin: 1px 8px;
            border-radius: var(--apple-radius-sm);
            text-decoration: none;
            color: var(--apple-text2);
            font-size: 0.855rem;
            font-weight: 500;
            transition: background 0.12s, color 0.12s;
            position: relative;
        }

        .ap-nav-item:hover {
            background: rgba(60, 60, 67, 0.08);
            color: var(--apple-text);
        }

        .ap-nav-item.active {
            background: rgba(0, 122, 255, 0.1);
            /* #007AFF (the brand accent, used elsewhere against white/dark backgrounds) only
               reaches ~3.6:1 against this light blue-tint background — a dedicated darker
               shade is used here specifically to clear WCAG AA's 4.5:1 for normal text. */
            color: #0051A8;
        }

        .ap-nav-item .ap-nav-icon {
            width: 28px;
            height: 28px;
            border-radius: 7px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 0.85rem;
            background: rgba(60, 60, 67, 0.08);
            color: var(--apple-text2);
            transition: background 0.12s, color 0.12s;
        }

        .ap-nav-item.active .ap-nav-icon,
        .ap-nav-item:hover .ap-nav-icon {
            background: var(--apple-blue);
            color: #fff;
        }

        /* Icon colors per section */
        .ap-nav-item[data-icon="dashboard"] .ap-nav-icon  { background: rgba(0,122,255,0.12); color: var(--apple-blue); }
        .ap-nav-item[data-icon="profile"] .ap-nav-icon    { background: rgba(88,86,214,0.12); color: #5856D6; }
        .ap-nav-item[data-icon="company"] .ap-nav-icon    { background: rgba(90,200,250,0.15); color: #0099FF; }
        .ap-nav-item[data-icon="browse"] .ap-nav-icon     { background: rgba(255,149,0,0.12); color: var(--apple-orange); }
        .ap-nav-item[data-icon="myreg"] .ap-nav-icon      { background: rgba(52,199,89,0.12); color: var(--apple-green); }
        .ap-nav-item[data-icon="lot"] .ap-nav-icon        { background: rgba(175,82,222,0.12); color: var(--apple-purple); }
        .ap-nav-item[data-icon="notif"] .ap-nav-icon      { background: rgba(255,59,48,0.1); color: var(--apple-red); }
        .ap-nav-item[data-icon="survey"] .ap-nav-icon    { background: rgba(52,199,89,0.12); color: var(--apple-green); }

        .ap-nav-item.active .ap-nav-icon {
            background: var(--apple-blue) !important;
            color: #fff !important;
        }

        .ap-nav-badge {
            margin-left: auto;
            background: #B91C1C;
            color: #fff;
            font-size: 0.62rem;
            font-weight: 700;
            padding: 1px 6px;
            border-radius: 999px;
            line-height: 1.6;
            flex-shrink: 0;
        }

        .ap-sidebar-spacer { flex: 1; }

        .ap-sidebar-footer {
            padding: 12px 16px;
            font-size: 0.7rem;
            color: var(--apple-muted);
            border-top: 1px solid var(--apple-sep);
            text-align: center;
        }

        /* ── Main Area ───────────────────────────── */
        .ap-main {
            margin-left: var(--sidebar-w);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background: var(--apple-bg);
        }

        /* ── Top Bar ─────────────────────────────── */
        .ap-topbar {
            height: var(--topbar-h);
            background: var(--apple-topbar);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border-bottom: 1px solid var(--apple-border);
            padding: 0 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
            position: sticky;
            top: 0;
            z-index: 900;
        }

        .ap-topbar-left { display: flex; align-items: center; gap: 10px; }

        .ap-topbar-title {
            font-size: 0.88rem;
            font-weight: 600;
            color: var(--apple-text);
            letter-spacing: -0.01em;
        }

        .ap-topbar-right { display: flex; align-items: center; gap: 8px; }

        .ap-icon-btn {
            width: 32px;
            height: 32px;
            border-radius: var(--apple-radius-sm);
            border: none;
            background: transparent;
            color: var(--apple-muted);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.12s, color 0.12s;
            font-size: 1rem;
            text-decoration: none;
        }

        .ap-icon-btn:hover {
            background: rgba(60, 60, 67, 0.08);
            color: var(--apple-text);
        }

        .ap-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--apple-blue), #5856D6);
            color: #fff;
            font-size: 0.72rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .ap-user-menu {
            display: flex;
            align-items: center;
            gap: 7px;
            padding: 4px 10px 4px 4px;
            border-radius: 999px;
            text-decoration: none;
            background: transparent;
            border: none;
            transition: background 0.12s;
            color: var(--apple-text);
            font-size: 0.83rem;
            font-weight: 500;
        }

        .ap-user-menu:hover {
            background: rgba(60, 60, 67, 0.08);
            color: var(--apple-text);
        }

        /* ── Dropdown (Bootstrap overrides) ─────── */
        .ap-dropdown-menu {
            border: 1px solid var(--apple-border);
            border-radius: var(--apple-radius);
            box-shadow: var(--apple-shadow-lg);
            padding: 6px;
            min-width: 180px;
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            background: rgba(255,255,255,0.96);
        }

        .ap-dropdown-menu .dropdown-item {
            border-radius: var(--apple-radius-sm);
            font-size: 0.845rem;
            padding: 7px 12px;
            color: var(--apple-text);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .ap-dropdown-menu .dropdown-item:hover {
            background: rgba(0, 122, 255, 0.08);
            color: var(--apple-blue-hov);
        }

        .ap-dropdown-menu .dropdown-item.text-danger:hover {
            background: rgba(255, 59, 48, 0.08);
            color: var(--apple-red);
        }

        .ap-dropdown-menu hr { border-color: var(--apple-sep); margin: 4px 0; }

        /* ── Content ─────────────────────────────── */
        .ap-content {
            flex: 1;
            padding: 24px 24px 20px;
        }

        /* ── Footer ──────────────────────────────── */
        .ap-footer-wrap {
            flex-shrink: 0;
        }

        /* Content panel — slides up above the menu row */
        .ap-footer-panel {
            display: none;
            background: rgba(255,255,255,0.96);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-top: 1px solid var(--apple-border);
            border-bottom: 1px solid var(--apple-border);
            padding: 24px 32px;
            position: relative;
            animation: footerPanelIn 0.22s ease;
        }

        .ap-footer-panel.show { display: block; }

        @keyframes footerPanelIn {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .ap-footer-panel-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--apple-text);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .ap-footer-panel-close {
            background: none;
            border: none;
            cursor: pointer;
            color: var(--apple-muted);
            font-size: 1.1rem;
            line-height: 1;
            padding: 2px 6px;
            border-radius: 6px;
            transition: background 0.12s, color 0.12s;
        }

        .ap-footer-panel-close:hover {
            background: rgba(0,0,0,0.06);
            color: var(--apple-text);
        }

        .ap-footer-panel-body {
            font-size: 0.85rem;
            line-height: 1.7;
            color: #3A3A3C;
            max-height: 220px;
            overflow-y: auto;
        }

        .ap-footer-panel-body a { color: var(--apple-blue-hov); text-decoration: underline; }

        /* Footer menu bar */
        .ap-footer {
            background: var(--apple-topbar);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-top: 1px solid var(--apple-border);
            padding: 14px 24px 12px;
            font-size: 0.75rem;
            color: var(--apple-muted);
        }

        .ap-footer-copy {
            text-align: center;
            margin-bottom: 10px;
        }

        .ap-footer-brand {
            font-weight: 700;
            color: var(--apple-blue-hov);
        }

        /* Footer bottom row */
        .ap-footer-bottom {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 24px;
        }

        /* Pyramid rows — plain text, left-aligned */
        .ap-footer-menu-rows { flex: 1; }

        .ap-footer-menu-row {
            display: flex;
            justify-content: flex-start;
            gap: 20px;
            margin-bottom: 4px;
            flex-wrap: wrap;
        }

        .ap-footer-menu-row:last-child { margin-bottom: 0; }

        .ap-footer-menu-btn {
            background: none;
            border: none;
            padding: 0;
            font-size: 0.78rem;
            color: var(--apple-muted);
            cursor: pointer;
            transition: color 0.15s;
            white-space: nowrap;
        }

        .ap-footer-menu-btn:hover,
        .ap-footer-menu-btn.active {
            background: none;
            border: none;
            color: var(--apple-blue-hov);
        }

        /* Follow Us */
        .ap-footer-follow { text-align: right; flex-shrink: 0; }

        .ap-footer-follow-label {
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--apple-muted);
            margin-bottom: 7px;
        }

        .ap-footer-follow-icons {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
        }

        .ap-footer-follow-icons a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 7px;
            background: rgba(0,0,0,0.05);
            color: var(--apple-muted);
            font-size: 0.9rem;
            text-decoration: none;
            transition: background 0.15s, color 0.15s;
        }

        .ap-footer-follow-icons a:hover {
            background: var(--apple-blue);
            color: #fff;
        }

        @media (max-width: 576px) {
            .ap-footer-bottom { flex-direction: column; }
            .ap-footer-follow { text-align: left; }
            .ap-footer-follow-icons { justify-content: flex-start; }
        }

        /* ── Mobile sidebar ──────────────────────── */
        @media (max-width: 991px) {
            .ap-sidebar {
                transform: translateX(-100%);
                transition: transform 0.25s cubic-bezier(0.25, 0.46, 0.45, 0.94);
                box-shadow: none;
            }
            .ap-sidebar.open {
                transform: translateX(0);
                box-shadow: var(--apple-shadow-lg);
            }
            .ap-main { margin-left: 0; }
            .ap-topbar-title { display: none; }
            .ap-overlay {
                display: none;
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,0.25);
                backdrop-filter: blur(2px);
                z-index: 999;
            }
            .ap-overlay.show { display: block; }
        }

        @media (max-width: 576px) {
            .ap-content { padding: 16px 14px; }
        }

        /* ── Enlarged text ───────────────────────── */
        html.text-large, html.text-large body { font-size: 1.04rem !important; }
        html.text-xlarge, html.text-xlarge body { font-size: 1.18rem !important; }

        .ap-text-size-btn.active {
            background: rgba(0, 122, 255, 0.1);
            color: var(--apple-blue-hov);
        }
    </style>

    @stack('styles')
</head>
<body>
@include('partials.skip-link')

@auth
{{-- ── Sidebar ─────────────────────────────────────────────────── --}}
<aside class="ap-sidebar" id="apSidebar">
    <div class="ap-sidebar-header">
        <a href="{{ route('dashboard') }}" class="ap-brand">
            <div class="ap-brand-icon">
                <svg viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect x="1.5" y="3" width="9" height="11" rx="1.8" fill="rgba(255,255,255,0.3)" stroke="rgba(255,255,255,0.7)" stroke-width="1.1"/>
                    <rect x="4.5" y="1.5" width="9" height="11" rx="1.8" fill="white"/>
                    <rect x="6.5" y="4.5" width="5" height="1.1" rx="0.55" fill="#007AFF"/>
                    <rect x="6.5" y="6.5" width="3.5" height="1.1" rx="0.55" fill="#007AFF" opacity="0.55"/>
                    <rect x="6.5" y="8.5" width="4" height="1.1" rx="0.55" fill="#007AFF" opacity="0.55"/>
                </svg>
            </div>
            <span class="ap-brand-name">undilot</span>
        </a>
    </div>

    @php
        /*
         * THE ONE PLACE A PORTAL NAV ENTRY IS DECLARED.
         *
         * Same reasoning as the admin layout: a route name spelled out in the
         * markup throws RouteNotFoundException from the LAYOUT when it goes, so
         * removing a module kills every portal page at once and the trace blames
         * the layout. One list is one place to look.
         *
         * The participant's portal is deliberately narrow — this is a lot draw,
         * not a profile system. Entries arrive as the phases land.
         */
        $apNav = [
            [
                'label' => 'Dashboard',
                'icon'  => 'house-door-fill',
                'route' => 'dashboard',
                'key'   => 'dashboard',
                // The lot page belongs to the dashboard: every draw is listed
                // there, and the lot is reached from its row.
                'match' => ['dashboard', 'zvote.*'],
            ],
        ];
    @endphp

    <nav>
        <div class="ap-sidebar-label">Main</div>

        @foreach($apNav as $apItem)
            <a href="{{ route($apItem['route']) }}" data-icon="{{ $apItem['key'] }}"
               class="ap-nav-item {{ request()->routeIs($apItem['match'] ?? $apItem['route']) ? 'active' : '' }}">
                <span class="ap-nav-icon"><i class="bi bi-{{ $apItem['icon'] }}"></i></span>
                {{ $apItem['label'] }}
            </a>
        @endforeach
    </nav>
</aside>

{{-- Mobile overlay --}}
<div class="ap-overlay" id="apOverlay"></div>

{{-- ── Main Area ─────────────────────────────────────────────────── --}}
<div class="ap-main" id="apMain">

    {{-- Top Bar --}}
    <header class="ap-topbar">
        <div class="ap-topbar-left">
            <button class="ap-icon-btn" id="apSidebarToggle" aria-label="Toggle sidebar">
                <i class="bi bi-sidebar"></i>
            </button>
            <span class="ap-topbar-title">@yield('page-title', 'Dashboard')</span>
        </div>

        <div class="ap-topbar-right">
            {{-- Text size toggle --}}
            <button class="ap-icon-btn ap-text-size-btn" id="apTextSizeToggle" title="Enlarge text">
                <i class="bi bi-fonts"></i>
            </button>

            {{-- NO NOTIFICATIONS BELL YET. $unreadNotificationCount is already
                 supplied here by AppServiceProvider's view composer, so when the
                 screen lands this is the markup and nothing else has to change. --}}

            {{-- User menu --}}
            <div class="dropdown">
                <button class="ap-user-menu dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="border:none;">
                    <div class="ap-avatar">{{ strtoupper(substr(Auth::user()->name, 0, 2)) }}</div>
                    <span class="d-none d-md-inline text-truncate" style="max-width:120px;">{{ Auth::user()->name }}</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end ap-dropdown-menu">
                    <li>
                        <a class="dropdown-item" href="{{ route('dashboard') }}">
                            <i class="bi bi-house-door" style="color:var(--apple-blue)"></i> Dashboard
                        </a>
                    </li>
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
        </div>
    </header>

    {{-- Page Content --}}
    <main id="main-content" class="ap-content">
        {{-- Fallback heading for pages with no visible h1. Pages that render
             their own declare @section('own-heading') so this does not produce
             a second h1 on the same page. --}}
        @hasSection('own-heading')
        @else
            <h1 class="visually-hidden">@yield('title', 'undilot')</h1>
        @endif
        @yield('content')
    </main>

    {{-- Footer --}}
    @php
        $footerMenus = \App\Models\FooterMenu::active()->orderBy('sort_order')->orderBy('id')->get();
        $footerRows  = [];
        $fmI         = 0;
        $fmPattern   = [5, 4];
        $fmPIdx      = 0;
        while ($fmI < $footerMenus->count()) {
            $fmSize = $fmPattern[$fmPIdx % 2];
            $footerRows[] = $footerMenus->slice($fmI, $fmSize)->values();
            $fmI += $fmSize;
            $fmPIdx++;
        }
    @endphp

    <div class="ap-footer-wrap">
        {{-- Content panel (hidden until a menu item is clicked) --}}
        <div class="ap-footer-panel" id="apFooterPanel">
            <div class="ap-footer-panel-title">
                <span id="apFooterPanelTitle"></span>
                <button class="ap-footer-panel-close" onclick="closeFooterPanel()" title="Close">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="ap-footer-panel-body" id="apFooterPanelBody"></div>
        </div>

        {{-- Menu bar --}}
        <footer class="ap-footer">
            <div class="ap-footer-copy">
                <span class="ap-footer-brand">undilot</span> &nbsp;&middot;&nbsp; &copy; {{ date('Y') }} All Rights Reserved.
            </div>

            <div class="ap-footer-bottom">
                {{-- Menu links — left --}}
                @if($footerMenus->count() > 0)
                    <nav class="ap-footer-menu-rows" aria-label="Footer menu">
                        @foreach($footerRows as $row)
                            <div class="ap-footer-menu-row">
                                @foreach($row as $fmItem)
                                    <button class="ap-footer-menu-btn"
                                            data-id="{{ $fmItem->id }}"
                                            data-title="{{ e($fmItem->title) }}"
                                            data-content="{{ $fmItem->content }}"
                                            onclick="toggleFooterPanel(this)">
                                        {{ $fmItem->title }}
                                    </button>
                                @endforeach
                            </div>
                        @endforeach
                    </nav>
                @else
                    <div></div>
                @endif

                {{-- Follow Us — right --}}
                @php $apSocialLinks = \App\Models\SocialLink::active()->orderBy('sort_order')->orderBy('id')->get(); @endphp
                @if($apSocialLinks->count() > 0)
                    <div class="ap-footer-follow">
                        <div class="ap-footer-follow-icons">
                            @foreach($apSocialLinks as $sl)
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
        </footer>
    </div>
</div>
@endauth

@guest
{{-- Carries the same id as the @auth branch's <main>: the skip link renders
     before this branch and targets #main-content either way, so a guest page
     without it would offer a link that lands nowhere. --}}
<main id="main-content" class="d-flex align-items-center justify-content-center min-vh-100" style="background:var(--apple-bg)">
    <div class="w-100" style="max-width:460px;padding:1.5rem;">
        {{-- Fallback heading for pages with no visible h1. Pages that render
             their own declare @section('own-heading') so this does not produce
             a second h1 on the same page. --}}
        @hasSection('own-heading')
        @else
            <h1 class="visually-hidden">@yield('title', 'undilot')</h1>
        @endif
        @yield('content')
    </div>
</main>
@endguest

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('apSidebar');
    const overlay = document.getElementById('apOverlay');
    const toggle  = document.getElementById('apSidebarToggle');
    if (!sidebar || !toggle) return;

    function openSidebar()  { sidebar.classList.add('open'); overlay?.classList.add('show'); }
    function closeSidebar() { sidebar.classList.remove('open'); overlay?.classList.remove('show'); }

    toggle.addEventListener('click', () => {
        if (window.innerWidth > 991) return;
        sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
    });

    overlay?.addEventListener('click', closeSidebar);

    window.addEventListener('resize', () => {
        if (window.innerWidth > 991) closeSidebar();
    });

    // ── Text size toggle (3 levels: 0=normal, 1=large, 2=xlarge) ──
    const textSizeBtn = document.getElementById('apTextSizeToggle');
    if (textSizeBtn) {
        const levels = ['', 'text-large', 'text-xlarge'];
        const labels = ['Enlarge text', 'Extra large text', 'Reset text size'];

        const applyLevel = (level) => {
            levels.forEach(c => { if (c) document.documentElement.classList.remove(c); });
            if (levels[level]) document.documentElement.classList.add(levels[level]);
            textSizeBtn.classList.toggle('active', level > 0);
            textSizeBtn.title = labels[level];
        };

        let currentLevel = parseInt(localStorage.getItem('textSizeLevel') || '0', 10);
        if (currentLevel < 0 || currentLevel > 2) currentLevel = 0;
        applyLevel(currentLevel);

        textSizeBtn.addEventListener('click', () => {
            currentLevel = (currentLevel + 1) % 3;
            localStorage.setItem('textSizeLevel', currentLevel);
            applyLevel(currentLevel);
        });
    }
});

// .table-responsive only becomes an actually-scrollable region on narrow viewports where table
// content overflows — at that point it needs to be keyboard-focusable (WCAG 2.1.1 / axe's
// scrollable-region-focusable), since a mouse/touch-only scroll target is unreachable for
// keyboard users.
document.addEventListener('DOMContentLoaded', function () {
    const markScrollableTables = () => {
        document.querySelectorAll('.table-responsive').forEach(el => {
            if (el.scrollWidth > el.clientWidth) {
                el.setAttribute('tabindex', '0');
                if (!el.hasAttribute('role')) el.setAttribute('role', 'region');
                if (!el.hasAttribute('aria-label')) el.setAttribute('aria-label', 'Scrollable table');
            } else {
                el.removeAttribute('tabindex');
            }
        });
    };
    markScrollableTables();
    window.addEventListener('resize', markScrollableTables);
});
</script>
@stack('scripts')
<script>
    // ── Footer panel ──────────────────────────────────────
    let activeFooterBtn = null;

    function toggleFooterPanel(btn) {
        const panel = document.getElementById('apFooterPanel');

        if (activeFooterBtn === btn && panel.classList.contains('show')) {
            closeFooterPanel();
            return;
        }

        // Update active button highlight
        document.querySelectorAll('.ap-footer-menu-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        activeFooterBtn = btn;

        document.getElementById('apFooterPanelTitle').textContent = btn.dataset.title;
        document.getElementById('apFooterPanelBody').innerHTML    = btn.dataset.content;
        panel.classList.add('show');

        // Scroll panel into view smoothly
        panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function closeFooterPanel() {
        document.getElementById('apFooterPanel').classList.remove('show');
        document.querySelectorAll('.ap-footer-menu-btn').forEach(b => b.classList.remove('active'));
        activeFooterBtn = null;
    }
</script>
</body>
</html>
