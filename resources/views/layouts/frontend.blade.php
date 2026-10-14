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

    @include('partials.ui-tokens')

    <style>
        /* The usahawan's portal: a navy app bar, one reading column, large type and
           large targets. On a desk the nav sits in the bar; on a phone it is a
           drawer, and the bar keeps only what a thumb needs. */
        :root {
            --ap-col: 1120px;
            --topbar-h: 64px;
            /* Old names some views still read inline. */
            --apple-blue: var(--ui-blue);
            --apple-blue-hov: var(--ui-blue-hover);
            --apple-green: var(--ui-ok);
            --apple-red: var(--ui-bad);
            --apple-orange: var(--ui-warn);
            --apple-purple: var(--ui-violet-text);
            --apple-bg: var(--ui-ground);
            --apple-card: var(--ui-surface);
            --apple-text: var(--ui-text);
            --apple-text2: var(--ui-text-2);
            --apple-muted: var(--ui-muted);
            --apple-border: var(--ui-border);
            --apple-sep: var(--ui-sep);
            --apple-radius: var(--ui-r-lg);
            --apple-radius-sm: var(--ui-r);
            --apple-font: var(--ui-font);
            --apple-shadow: var(--ui-shadow-1);
            --apple-shadow-lg: var(--ui-shadow-pop);
        }

        html, body { min-height: 100%; background: var(--ui-ground); color: var(--ui-text); font-size: 1rem; line-height: 1.55; overflow-x: hidden; }

        /* ── Drawer (phone) ──────────────────────── */
        .ap-sidebar {
            position: fixed; inset: 0 auto 0 0; z-index: 1000; width: 280px;
            display: flex; flex-direction: column; overflow-y: auto;
            background: var(--ui-navy-900); color: #fff;
            transform: translateX(-100%); transition: transform 0.28s var(--ui-ease);
        }
        .ap-sidebar.open { transform: translateX(0); box-shadow: var(--ui-shadow-pop); }
        .ap-sidebar-header { padding: 18px 18px 16px; border-bottom: 1px solid rgba(255, 255, 255, 0.08); }
        .ap-sidebar-label { padding: 18px 20px 6px; font-size: 0.8rem; font-weight: 600; color: var(--ui-on-navy-dim); }
        .ap-nav-item {
            display: flex; align-items: center; gap: 12px; min-height: 52px;
            margin: 2px 10px; padding: 10px 12px; border-radius: var(--ui-r);
            color: var(--ui-on-navy-3); font-size: 1.02rem; font-weight: 500; text-decoration: none;
        }
        .ap-nav-item:hover { background: rgba(255, 255, 255, 0.07); color: #fff; }
        .ap-nav-item.active { background: var(--ui-navy-700); color: #fff; font-weight: 600; box-shadow: inset 3px 0 0 var(--ui-gold); }
        .ap-nav-item .ap-nav-icon { width: 22px; display: grid; place-items: center; font-size: 1.1rem; color: var(--ui-on-navy-icon); }
        .ap-nav-item.active .ap-nav-icon { color: var(--ui-gold); }
        .ap-nav-badge { margin-left: auto; padding: 1px 7px; border-radius: 999px; background: var(--ui-bad); color: #fff; font-size: 0.72rem; font-weight: 700; }
        .ap-sidebar-spacer { flex: 1; }
        .ap-overlay { display: none; position: fixed; inset: 0; z-index: 999; background: rgba(7, 26, 51, 0.5); }
        .ap-overlay.show { display: block; }

        /* ── Brand ───────────────────────────────── */
        .ap-brand { display: flex; align-items: center; gap: 10px; color: #fff; text-decoration: none; }
        .ap-brand:hover { color: #fff; }
        .ap-brand-icon { width: 34px; height: 34px; flex-shrink: 0; display: grid; place-items: center; border-radius: 9px; background: var(--ui-gold); color: var(--ui-navy-950); }
        .ap-brand-name { font-size: 1.1rem; font-weight: 700; letter-spacing: -0.01em; color: #fff; }

        /* ── App bar ─────────────────────────────── */
        .ap-main { min-height: 100vh; display: flex; flex-direction: column; background: var(--ui-ground); }
        .ap-topbar {
            position: sticky; top: 0; z-index: 900; flex-shrink: 0;
            height: var(--topbar-h); padding: 0 max(16px, calc((100% - var(--ap-col)) / 2));
            display: flex; align-items: center; justify-content: space-between; gap: 12px;
            background: var(--ui-navy-900); color: #fff;
        }
        .ap-topbar-left, .ap-topbar-right { display: flex; align-items: center; gap: 6px; min-width: 0; }
        .ap-topbar-title { font-size: 1.02rem; font-weight: 650; color: #fff; }
        .ap-topbar .ap-brand { margin-right: 18px; }
        .ap-topnav { display: flex; align-items: center; gap: 4px; }
        .ap-topnav a {
            display: inline-flex; align-items: center; gap: 8px; min-height: 40px; padding: 0 14px;
            border-radius: var(--ui-r); color: var(--ui-on-navy-3); font-weight: 600; font-size: 0.95rem; text-decoration: none;
        }
        .ap-topnav a:hover { background: rgba(255, 255, 255, 0.08); color: #fff; }
        .ap-topnav a.active { color: #fff; box-shadow: inset 0 -3px 0 var(--ui-gold); border-radius: var(--ui-r) var(--ui-r) 0 0; }
        .ap-icon-btn {
            position: relative; width: 44px; height: 44px; flex-shrink: 0;
            display: grid; place-items: center; border: none; border-radius: var(--ui-r);
            background: transparent; color: var(--ui-on-navy-3); font-size: 1.15rem; text-decoration: none; cursor: pointer;
            transition: background-color var(--ui-fast) var(--ui-ease), color var(--ui-fast) var(--ui-ease);
        }
        .ap-icon-btn:hover { background: rgba(255, 255, 255, 0.08); color: #fff; }
        .ap-icon-btn .badge { border: 2px solid var(--ui-navy-900); }
        .ap-text-size-btn.active { background: rgba(255, 255, 255, 0.14); color: #fff; }
        .ap-user-menu {
            display: flex; align-items: center; gap: 8px; min-height: 44px; padding: 4px 10px 4px 4px;
            border: none; border-radius: 999px; background: transparent; color: #fff; font-size: 0.95rem; font-weight: 600;
        }
        .ap-user-menu:hover { background: rgba(255, 255, 255, 0.08); color: #fff; }
        .ap-avatar { width: 34px; height: 34px; flex-shrink: 0; display: grid; place-items: center; border-radius: 50%; background: #fff; color: var(--ui-navy-900); font-size: 0.78rem; font-weight: 700; }
        .ap-dropdown-menu { min-width: 200px; padding: 6px; border: 1px solid var(--ui-border); border-radius: var(--ui-r-lg); background: var(--ui-surface); box-shadow: var(--ui-shadow-pop); }
        .ap-dropdown-menu .dropdown-item { display: flex; align-items: center; gap: 10px; min-height: 44px; padding: 8px 12px; border-radius: var(--ui-r-sm); color: var(--ui-text); font-size: 0.95rem; }
        .ap-dropdown-menu .dropdown-item:hover { background: var(--ui-neutral-tint); color: var(--ui-text); }
        .ap-dropdown-menu .dropdown-item.text-danger { color: var(--ui-bad-text) !important; }
        .ap-dropdown-menu .dropdown-item.text-danger:hover { background: var(--ui-bad-tint); }
        .ap-dropdown-menu hr { margin: 4px 0; border-color: var(--ui-sep); }

        /* ── Reading column ──────────────────────── */
        .ap-content { flex: 1; width: 100%; max-width: calc(var(--ap-col) + 48px); margin: 0 auto; padding: 32px 24px 40px; }

        /* ── Footer ──────────────────────────────── */
        .ap-footer-wrap { flex-shrink: 0; }
        .ap-footer-panel { display: none; position: relative; padding: 24px; background: var(--ui-surface); border-top: 1px solid var(--ui-border); }
        .ap-footer-panel.show { display: block; }
        .ap-footer-panel-title { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; font-size: 1.05rem; font-weight: 700; }
        .ap-footer-panel-close { width: 40px; height: 40px; border: none; border-radius: var(--ui-r); background: none; color: var(--ui-muted); font-size: 1.1rem; }
        .ap-footer-panel-close:hover { background: var(--ui-neutral-tint); color: var(--ui-text); }
        .ap-footer-panel-body { max-height: 240px; overflow-y: auto; color: var(--ui-text-2); line-height: 1.7; }
        .ap-footer { padding: 20px 24px; background: var(--ui-surface); border-top: 1px solid var(--ui-border); font-size: 0.875rem; color: var(--ui-muted); }
        .ap-footer-copy { max-width: var(--ap-col); margin: 0 auto 12px; }
        .ap-footer-brand { font-weight: 700; color: var(--ui-text); }
        .ap-footer-bottom { display: flex; align-items: flex-start; justify-content: space-between; gap: 24px; max-width: var(--ap-col); margin: 0 auto; }
        .ap-footer-menu-rows { flex: 1; }
        .ap-footer-menu-row { display: flex; flex-wrap: wrap; gap: 4px 20px; margin-bottom: 4px; }
        .ap-footer-menu-btn { padding: 6px 0; border: none; background: none; color: var(--ui-text-2); font-size: 0.9rem; text-decoration: underline; text-underline-offset: 3px; cursor: pointer; }
        .ap-footer-menu-btn:hover, .ap-footer-menu-btn.active { color: var(--ui-blue-text); }
        .ap-footer-follow { flex-shrink: 0; text-align: right; }
        .ap-footer-follow-label { margin-bottom: 6px; font-size: 0.82rem; font-weight: 600; color: var(--ui-muted); }
        .ap-footer-follow-icons { display: flex; justify-content: flex-end; gap: 8px; }
        .ap-footer-follow-icons a { width: 40px; height: 40px; display: grid; place-items: center; border-radius: var(--ui-r); background: var(--ui-neutral-tint); color: var(--ui-text-2); text-decoration: none; }
        .ap-footer-follow-icons a:hover { background: var(--ui-navy-900); color: #fff; }

        /* ── Desk widths: the bar carries the nav, no drawer ── */
        @media (min-width: 992px) {
            .ap-sidebar, .ap-overlay, #apSidebarToggle { display: none !important; }
            .ap-topbar-title { display: none; }
        }
        @media (max-width: 991.98px) {
            .ap-topnav, .ap-topbar .ap-brand { display: none; }
        }
        @media (max-width: 576px) {
            .ap-content { padding: 20px 16px 32px; }
            .ap-footer-bottom { flex-direction: column; }
            .ap-footer-follow { text-align: left; }
            .ap-footer-follow-icons { justify-content: flex-start; }
            .ap-user-menu span { display: none; }
        }

        /* The portal's text-size toggle: older eyes come first here. */
        html.text-large, html.text-large body { font-size: 1.12rem !important; }
        html.text-xlarge, html.text-xlarge body { font-size: 1.26rem !important; }
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
            <span class="ap-brand-icon" aria-hidden="true"><i class="bi bi-grid-3x3-gap-fill"></i></span>
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
                <i class="bi bi-list" aria-hidden="true"></i>
            </button>
            <span class="ap-topbar-title">@yield('page-title', 'Dashboard')</span>
            <a href="{{ route('dashboard') }}" class="ap-brand">
                <span class="ap-brand-icon" aria-hidden="true"><i class="bi bi-grid-3x3-gap-fill"></i></span>
                <span class="ap-brand-name">undilot</span>
            </a>
            <nav class="ap-topnav" aria-label="Main">
                @foreach($apNav as $apItem)
                    <a href="{{ route($apItem['route']) }}" class="{{ request()->routeIs($apItem['match'] ?? $apItem['route']) ? 'active' : '' }}">
                        {{ $apItem['label'] }}
                    </a>
                @endforeach
            </nav>
        </div>

        <div class="ap-topbar-right">
            {{-- Text size toggle --}}
            <button class="ap-icon-btn ap-text-size-btn" id="apTextSizeToggle" title="Enlarge text">
                <i class="bi bi-fonts"></i>
            </button>

            {{-- $unreadNotificationCount comes from AppServiceProvider's view
                 composer, which names this layout. --}}
            <a href="{{ route('notifications.index') }}" class="ap-icon-btn position-relative"
               aria-label="Notifications{{ $unreadNotificationCount ? ", {$unreadNotificationCount} unread" : '' }}">
                <i class="bi bi-bell"></i>
                @if($unreadNotificationCount)
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:0.6rem;">
                        {{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }}
                    </span>
                @endif
            </a>

            {{-- User menu --}}
            <div class="dropdown">
                <button class="ap-user-menu dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="border:none;">
                    <div class="ap-avatar">{{ strtoupper(substr(Auth::user()->name, 0, 2)) }}</div>
                    <span class="d-none d-md-inline text-truncate" style="max-width:120px;">{{ Auth::user()->name }}</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end ap-dropdown-menu">
                    <li>
                        <a class="dropdown-item" href="{{ route('dashboard') }}">
                            <i class="bi bi-house-door"></i> Dashboard
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
