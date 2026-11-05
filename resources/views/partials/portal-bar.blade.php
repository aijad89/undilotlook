{{--
    THE participant top bar: the portal (layouts.frontend) and the public pages
    (layouts.public) both include this, so a usahawan walking from Upcoming Events
    to their dashboard and back never sees the bar change.

    Navy, on the 1000px column. On a desk the nav sits in the bar; on a phone it is
    a drawer, and the bar keeps only what a thumb needs. Signed out it offers
    Events, the text size and Sign In; signed in, Events, Dashboard, the bell and
    the user menu.

    Reads $unreadNotificationCount, so it is named in AppServiceProvider's view
    composer list.

    The page title on a phone comes from @section('page-title').
--}}
<style>
    :root { --topbar-h: 64px; }

    /* ── Drawer (phone) ──────────────────────── */
    .ap-sidebar {
        position: fixed; inset: 0 auto 0 0; z-index: 1000; width: 280px;
        display: flex; flex-direction: column; overflow-y: auto;
        background: var(--ui-navy-900); color: #fff;
        transform: translateX(-100%); transition: transform 0.28s var(--ui-ease);
    }
    .ap-sidebar.open { transform: translateX(0); box-shadow: var(--ui-shadow-pop); }
    .ap-sidebar-header { padding: 18px 18px 16px; border-bottom: 1px solid rgba(255, 255, 255, 0.08); }
    .ap-sidebar-label { padding: 18px 20px 6px; font-size: 0.8125rem; font-weight: 600; color: var(--ui-on-navy-dim); }
    .ap-nav-item {
        display: flex; align-items: center; gap: 12px; min-height: 52px;
        margin: 2px 10px; padding: 10px 12px; border-radius: var(--ui-r);
        color: var(--ui-on-navy-3); font-size: 1rem; font-weight: 500; text-decoration: none;
    }
    .ap-nav-item:hover { background: rgba(255, 255, 255, 0.07); color: #fff; }
    .ap-nav-item.active { background: var(--ui-navy-700); color: #fff; font-weight: 600; box-shadow: inset 3px 0 0 var(--ui-gold); }
    .ap-nav-item .ap-nav-icon { width: 22px; display: grid; place-items: center; font-size: 1.125rem; color: var(--ui-on-navy-icon); }
    .ap-nav-item.active .ap-nav-icon { color: var(--ui-gold); }
    .ap-overlay { display: none; position: fixed; inset: 0; z-index: 999; background: rgba(7, 26, 51, 0.5); }
    .ap-overlay.show { display: block; }

    /* ── Brand ───────────────────────────────── */
    .ap-brand { display: flex; align-items: center; gap: 10px; color: #fff; text-decoration: none; }
    .ap-brand:hover { color: #fff; }
    .ap-brand-icon { width: 34px; height: 34px; flex-shrink: 0; display: grid; place-items: center; border-radius: 8px; background: var(--ui-gold); color: var(--ui-navy-950); }
    .ap-brand-name { font-size: 1.125rem; font-weight: 700; letter-spacing: -0.01em; color: #fff; }

    /* ── Bar ─────────────────────────────────── */
    .ap-topbar {
        position: sticky; top: 0; z-index: 900; flex-shrink: 0;
        height: var(--topbar-h); padding: 0 max(var(--ui-page-pad), calc((100% - var(--ui-page-w)) / 2));
        display: flex; align-items: center; justify-content: space-between; gap: 12px;
        background: var(--ui-navy-900); color: #fff;
    }
    .ap-topbar-left, .ap-topbar-right { display: flex; align-items: center; gap: 6px; min-width: 0; }
    .ap-topbar-title { overflow: hidden; font-size: 1rem; font-weight: 650; color: #fff; text-overflow: ellipsis; white-space: nowrap; }
    .ap-topbar .ap-brand { margin-right: 18px; }
    .ap-topnav { display: flex; align-items: center; gap: 4px; }
    .ap-topnav a {
        display: inline-flex; align-items: center; gap: 8px; min-height: 40px; padding: 0 14px;
        border-radius: var(--ui-r); color: var(--ui-on-navy-3); font-weight: 600; font-size: 1rem; text-decoration: none;
    }
    .ap-topnav a:hover { background: rgba(255, 255, 255, 0.08); color: #fff; }
    .ap-topnav a.active { color: #fff; box-shadow: inset 0 -3px 0 var(--ui-gold); border-radius: var(--ui-r) var(--ui-r) 0 0; }
    .ap-icon-btn {
        position: relative; width: 44px; height: 44px; flex-shrink: 0;
        display: grid; place-items: center; border: none; border-radius: var(--ui-r);
        background: transparent; color: var(--ui-on-navy-3); font-size: 1.125rem; text-decoration: none; cursor: pointer;
        transition: background-color var(--ui-fast) var(--ui-ease), color var(--ui-fast) var(--ui-ease);
    }
    .ap-icon-btn:hover { background: rgba(255, 255, 255, 0.08); color: #fff; }
    .ap-icon-btn .badge { border: 2px solid var(--ui-navy-900); }
    .ap-text-size-btn.active { background: rgba(255, 255, 255, 0.14); color: #fff; }
    .ap-signin { display: inline-flex; align-items: center; min-height: 42px; margin-left: 4px; padding: 0 18px; border: 1px solid #fff; border-radius: var(--ui-r); background: #fff; color: var(--ui-navy-900); font-size: 1rem; font-weight: 600; text-decoration: none; white-space: nowrap; }
    .ap-signin:hover { background: var(--ui-blue-tint); color: var(--ui-navy-900); }
    .ap-user-menu {
        display: flex; align-items: center; gap: 8px; min-height: 44px; padding: 4px 10px 4px 4px;
        border: none; border-radius: 999px; background: transparent; color: #fff; font-size: 1rem; font-weight: 600;
    }
    .ap-user-menu:hover { background: rgba(255, 255, 255, 0.08); color: #fff; }
    .ap-avatar { width: 34px; height: 34px; flex-shrink: 0; display: grid; place-items: center; border-radius: 50%; background: #fff; color: var(--ui-navy-900); font-size: 0.8125rem; font-weight: 700; }
    .ap-dropdown-menu { min-width: 200px; padding: 6px; border: 1px solid var(--ui-border); border-radius: var(--ui-r-lg); background: var(--ui-surface); box-shadow: var(--ui-shadow-pop); }
    .ap-dropdown-menu .dropdown-item { display: flex; align-items: center; gap: 10px; min-height: 44px; padding: 8px 12px; border-radius: var(--ui-r-sm); color: var(--ui-text); font-size: 1rem; }
    .ap-dropdown-menu .dropdown-item:hover { background: var(--ui-neutral-tint); color: var(--ui-text); }
    .ap-dropdown-menu .dropdown-item.text-danger { color: var(--ui-bad-text) !important; }
    .ap-dropdown-menu .dropdown-item.text-danger:hover { background: var(--ui-bad-tint); }
    .ap-dropdown-menu hr { margin: 4px 0; border-color: var(--ui-sep); }

    /* ── Desk widths: the bar carries the nav, no drawer ── */
    @media (min-width: 992px) {
        .ap-sidebar, .ap-overlay, #apSidebarToggle { display: none !important; }
        .ap-topbar-title { display: none; }
    }
    @media (max-width: 991.98px) {
        .ap-topnav, .ap-topbar .ap-brand { display: none; }
    }
    @media (max-width: 576px) {
        .ap-user-menu span { display: none; }
    }

    /* The text-size toggle: older eyes come first here. */
    html.text-large, html.text-large body { font-size: 1.125rem !important; }
    html.text-xlarge, html.text-xlarge body { font-size: 1.25rem !important; }
</style>

@php
    /*
     * THE ONE PLACE A PARTICIPANT NAV ENTRY IS DECLARED.
     *
     * A route name spelled out in the markup throws RouteNotFoundException from
     * the LAYOUT when it goes, so removing a module kills every page at once and
     * the trace blames the layout. One list is one place to look.
     *
     * Deliberately narrow — this is a lot draw, not a profile system.
     */
    $apNav = [
        ['label' => 'Events', 'icon' => 'calendar-event-fill', 'route' => 'home', 'key' => 'events', 'match' => ['home', 'events.show']],
    ];
    if (auth()->check()) {
        // The lot page belongs to the dashboard: every draw is listed there,
        // and the lot is reached from its row.
        $apNav[] = ['label' => 'Dashboard', 'icon' => 'house-door-fill', 'route' => 'dashboard', 'key' => 'dashboard', 'match' => ['dashboard', 'zvote.*']];
    }
    $apHome = auth()->check() ? route('dashboard') : route('home');
@endphp

{{-- ── Drawer (phone) ───────────────────────────────────────────── --}}
<aside class="ap-sidebar" id="apSidebar">
    <div class="ap-sidebar-header">
        <a href="{{ $apHome }}" class="ap-brand">
            <span class="ap-brand-icon" aria-hidden="true"><i class="bi bi-grid-3x3-gap-fill"></i></span>
            <span class="ap-brand-name">undilot</span>
        </a>
    </div>
    <nav>
        <div class="ap-sidebar-label">Main</div>
        @foreach($apNav as $apItem)
            <a href="{{ route($apItem['route']) }}" data-icon="{{ $apItem['key'] }}"
               class="ap-nav-item {{ request()->routeIs($apItem['match']) ? 'active' : '' }}">
                <span class="ap-nav-icon"><i class="bi bi-{{ $apItem['icon'] }}"></i></span>
                {{ $apItem['label'] }}
            </a>
        @endforeach
    </nav>
</aside>
<div class="ap-overlay" id="apOverlay"></div>

{{-- ── Bar ──────────────────────────────────────────────────────── --}}
<header class="ap-topbar">
    <div class="ap-topbar-left">
        <button class="ap-icon-btn" id="apSidebarToggle" type="button" aria-label="Open menu">
            <i class="bi bi-list" aria-hidden="true"></i>
        </button>
        <span class="ap-topbar-title">@yield('page-title', 'undilot')</span>
        <a href="{{ $apHome }}" class="ap-brand">
            <span class="ap-brand-icon" aria-hidden="true"><i class="bi bi-grid-3x3-gap-fill"></i></span>
            <span class="ap-brand-name">undilot</span>
        </a>
        <nav class="ap-topnav" aria-label="Main">
            @foreach($apNav as $apItem)
                <a href="{{ route($apItem['route']) }}" class="{{ request()->routeIs($apItem['match']) ? 'active' : '' }}">
                    {{ $apItem['label'] }}
                </a>
            @endforeach
        </nav>
    </div>

    <div class="ap-topbar-right">
        <button class="ap-icon-btn ap-text-size-btn" id="apTextSizeToggle" type="button" title="Enlarge text" aria-label="Enlarge text">
            <span style="font-size:1rem;font-weight:700;letter-spacing:-0.02em;" aria-hidden="true">Aa</span>
        </button>

        @auth
            <a href="{{ route('notifications.index') }}" class="ap-icon-btn position-relative"
               aria-label="Notifications{{ $unreadNotificationCount ? ", {$unreadNotificationCount} unread" : '' }}">
                <i class="bi bi-bell"></i>
                @if($unreadNotificationCount)
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:0.75rem;">
                        {{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }}
                    </span>
                @endif
            </a>

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
        @else
            <a href="{{ route('login') }}" class="ap-signin">Sign In</a>
        @endauth
    </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // ── Drawer (phone) ──
    const sidebar = document.getElementById('apSidebar');
    const overlay = document.getElementById('apOverlay');
    const toggle  = document.getElementById('apSidebarToggle');
    const openSidebar  = () => { sidebar.classList.add('open'); overlay.classList.add('show'); };
    const closeSidebar = () => { sidebar.classList.remove('open'); overlay.classList.remove('show'); };
    toggle.addEventListener('click', () => {
        if (window.innerWidth > 991) return;
        sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
    });
    overlay.addEventListener('click', closeSidebar);
    window.addEventListener('resize', () => { if (window.innerWidth > 991) closeSidebar(); });

    // ── Text size (3 levels: 0 normal, 1 large, 2 extra large) ──
    const textSizeBtn = document.getElementById('apTextSizeToggle');
    const levels = ['', 'text-large', 'text-xlarge'];
    const labels = ['Enlarge text', 'Extra large text', 'Reset text size'];
    const applyLevel = (level) => {
        levels.forEach(c => { if (c) document.documentElement.classList.remove(c); });
        if (levels[level]) document.documentElement.classList.add(levels[level]);
        textSizeBtn.classList.toggle('active', level > 0);
        textSizeBtn.title = labels[level];
        textSizeBtn.setAttribute('aria-label', labels[level]);
    };
    let currentLevel = parseInt(localStorage.getItem('textSizeLevel') || '0', 10);
    if (!(currentLevel >= 0 && currentLevel <= 2)) currentLevel = 0;
    applyLevel(currentLevel);
    textSizeBtn.addEventListener('click', () => {
        currentLevel = (currentLevel + 1) % 3;
        localStorage.setItem('textSizeLevel', currentLevel);
        applyLevel(currentLevel);
    });
});
</script>
