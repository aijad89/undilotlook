{{--
    THE participant top bar: the portal (layouts.frontend) and the public pages
    (layouts.public) both include this, so a usahawan walking from Upcoming Events
    to their dashboard and back never sees the bar change.

    Navy, on the 1000px column. Signed in, the nav lives in a navy rail down the
    left that looks exactly like the office's: fixed on a desk (the menu button
    hides and shows it), a drawer on a phone. It holds the nav, Notifications and
    Sign Out; the bar keeps the page title, text size, the bell and the user
    menu. The layouts mark a signed-in body `ap-with-rail` to make room for it.
    Signed out there is no rail: on a desk the nav sits in the bar, on a phone
    it is the drawer, with Events, the text size and Sign In.

    Reads $unreadNotificationCount, so it is named in AppServiceProvider's view
    composer list.

    The page title on a phone comes from @section('page-title').
--}}
<style>
    :root { --topbar-h: 64px; }

    /* ── Drawer (every screen) ───────────────── */
    /* Hidden is `visibility: hidden` as well as off-screen, so a closed drawer's
       links are out of the tab order; it waits for the slide before hiding. */
    .ap-sidebar {
        position: fixed; inset: 0 auto 0 0; z-index: 1000; width: 264px; max-width: 86vw;
        display: flex; flex-direction: column; overflow-y: auto; overscroll-behavior: contain;
        background: var(--ui-navy-900); color: #fff;
        transform: translateX(-100%); visibility: hidden;
        transition: transform 0.28s var(--ui-ease), visibility 0s linear 0.28s;
    }
    .ap-sidebar.open { transform: translateX(0); visibility: visible; box-shadow: var(--ui-shadow-pop); transition: transform 0.28s var(--ui-ease); }
    .ap-sidebar { scrollbar-color: rgba(255, 255, 255, 0.2) transparent; }
    /* From here down, the office rail (layouts.admin #sidebar), value for value:
       one menu, whichever side of the counter you sit. */
    .ap-sidebar-header { display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 20px 12px 18px 20px; border-bottom: 1px solid rgba(255, 255, 255, 0.08); }
    .ap-sidebar .ap-brand { gap: 12px; }
    .ap-sidebar .ap-brand-icon { width: 36px; height: 36px; font-size: 1rem; }
    .ap-sidebar .ap-brand-name { display: block; font-size: 1rem; line-height: 1.2; }
    .ap-brand-sub { display: block; font-size: 0.8125rem; color: var(--ui-on-navy-2); line-height: 1.3; }
    .ap-sidebar-nav { list-style: none; margin: 4px 0 0; padding: 0; }
    .ap-nav-item {
        position: relative; display: flex; align-items: center; gap: 12px;
        margin: 2px 10px; padding: 9px 12px; min-height: 40px; border-radius: var(--ui-r);
        color: var(--ui-on-navy-3); font-size: 0.875rem; font-weight: 500; text-decoration: none;
        transition: background-color var(--ui-fast) var(--ui-ease), color var(--ui-fast) var(--ui-ease);
    }
    .ap-nav-item i { width: 18px; flex-shrink: 0; text-align: center; font-size: 1rem; color: var(--ui-on-navy-icon); transition: color var(--ui-fast) var(--ui-ease); }
    .ap-nav-item:hover { background: rgba(255, 255, 255, 0.07); color: #fff; }
    .ap-nav-item:hover i { color: #fff; }
    .ap-nav-item.active { background: var(--ui-navy-700); color: #fff; font-weight: 600; }
    .ap-nav-item.active i { color: var(--ui-gold); }
    /* Gold is "you are here": a bar on the active item, nowhere else. */
    .ap-nav-item.active::before { content: ""; position: absolute; left: -10px; top: 8px; bottom: 8px; width: 3px; border-radius: 0 3px 3px 0; background: var(--ui-gold); }
    .ap-nav-count { margin-left: auto; padding: 2px 7px; border-radius: 999px; background: var(--ui-bad); color: #fff; font-size: 0.75rem; font-weight: 700; line-height: 1.3; font-variant-numeric: tabular-nums; }
    /* Sign Out is the quieter one, as in the office. */
    button.ap-nav-item { width: calc(100% - 20px); border: 0; background: transparent; font-family: inherit; text-align: left; color: var(--ui-on-navy-soft); cursor: pointer; }
    button.ap-nav-item:hover { background: rgba(255, 255, 255, 0.07); color: #fff; }
    html.ap-drawer-open { overflow: hidden; }
    @media (prefers-reduced-motion: reduce) {
        .ap-sidebar, .ap-sidebar.open { transition: none; }
    }
    .ap-overlay { display: none; position: fixed; inset: 0; z-index: 999; background: rgba(7, 26, 51, 0.45); }
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
    .ap-signin-dev { background: transparent; color: #fff; }
    .ap-signin-dev:hover { background: rgba(255, 255, 255, 0.08); color: #fff; }
    /* A dev shortcut; on a phone it would push Sign In off the bar. */
    @media (max-width: 575.98px) { .ap-signin-dev { display: none; } }
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

    /* ── Desk widths ── */
    /* Signed out: the nav sits in the bar, and the menu button is the drawer's.
       Signed in: the drawer stands as a fixed rail and the page moves over for
       it; the button hides and shows it, as the office's does. */
    @media (min-width: 992px) {
        :root { --ap-rail-w: 264px; }
        .ap-topbar-title { display: none; }
        #apSidebarToggle { margin-right: 6px; }
        body:not(.ap-with-rail) #apSidebarToggle { display: none; }

        /* The window's width no longer says how much room the page has, so the
           column keeps a gutter beside the rail, 24px as in the office. */
        body.ap-with-rail { --ui-page-pad: 24px; --ui-page-max: calc(var(--ui-page-w) + 2 * 24px); padding-left: var(--ap-rail-w); }
        /* Animated only when the button moves it: these styles arrive after
           <body> opens, so a transition here would slide every page in on load. */
        body.ap-with-rail.ap-rail-moving { transition: padding-left 0.28s var(--ui-ease); }
        body.ap-with-rail .ap-sidebar { z-index: 950; width: var(--ap-rail-w); max-width: none; transform: none; visibility: visible; box-shadow: none; transition: transform 0.28s var(--ui-ease); }
        body.ap-with-rail #apSidebarClose, body.ap-with-rail .ap-overlay { display: none !important; }
        body.ap-with-rail .ap-topbar .ap-brand, body.ap-with-rail .ap-topnav { display: none; }
        body.ap-with-rail .ap-topbar-title { display: block; }
        body.ap-with-rail.ap-rail-hidden { padding-left: 0; }
        body.ap-with-rail.ap-rail-hidden .ap-sidebar { transform: translateX(-100%); visibility: hidden; transition: transform 0.28s var(--ui-ease), visibility 0s linear 0.28s; }
    }
    @media (min-width: 992px) and (prefers-reduced-motion: reduce) {
        body.ap-with-rail.ap-rail-moving, body.ap-with-rail .ap-sidebar, body.ap-with-rail.ap-rail-hidden .ap-sidebar { transition: none; }
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
        ['label' => 'Events', 'icon' => 'calendar-event', 'route' => 'home', 'key' => 'events', 'match' => ['home', 'events.show']],
    ];
    if (auth()->check()) {
        // The lot page belongs to the dashboard: every draw is listed there,
        // and the lot is reached from its row.
        $apNav[] = ['label' => 'Dashboard', 'icon' => 'house-door', 'route' => 'dashboard', 'key' => 'dashboard', 'match' => ['dashboard', 'zvote.*']];
        $apNav[] = ['label' => 'Past events', 'icon' => 'clock-history', 'route' => 'past-events', 'key' => 'past-events', 'match' => ['past-events'], 'drawer' => true];
        // The drawer only: the bar has its bell.
        $apNav[] = ['label' => 'Notifications', 'icon' => 'bell', 'route' => 'notifications.index', 'key' => 'notifications', 'match' => ['notifications.*'], 'drawer' => true, 'count' => $unreadNotificationCount];
        $apNav[] = ['label' => 'Profile', 'icon' => 'person', 'route' => 'profile', 'key' => 'profile', 'match' => ['profile', 'profile.*'], 'drawer' => true];
    }
    $apHome = auth()->check() ? route('dashboard') : route('home');
@endphp

{{-- ── Drawer (every screen) ─────────────────────────────────────── --}}
<aside class="ap-sidebar" id="apSidebar" aria-label="Menu">
    <div class="ap-sidebar-header">
        <a href="{{ $apHome }}" class="ap-brand">
            <span class="ap-brand-icon" aria-hidden="true"><i class="bi bi-grid-3x3-gap-fill"></i></span>
            <span>
                <span class="ap-brand-name">undilot</span>
                <span class="ap-brand-sub">Participant Portal</span>
            </span>
        </a>
        <button class="ap-icon-btn" id="apSidebarClose" type="button" aria-label="Close menu">
            <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
    </div>
    <nav aria-label="Main">
        <ul class="ap-sidebar-nav">
            @foreach($apNav as $apItem)
                @php $apActive = request()->routeIs($apItem['match']); @endphp
                <li>
                    <a href="{{ route($apItem['route']) }}" data-icon="{{ $apItem['key'] }}"
                       class="ap-nav-item {{ $apActive ? 'active' : '' }}" @if($apActive) aria-current="page" @endif>
                        <i class="bi bi-{{ $apItem['icon'] }}" aria-hidden="true"></i> {{ $apItem['label'] }}
                        @if(! empty($apItem['count']))
                            <span class="ap-nav-count">{{ $apItem['count'] > 99 ? '99+' : $apItem['count'] }}<span class="visually-hidden"> unread</span></span>
                        @endif
                    </a>
                </li>
            @endforeach

            <li class="mt-2">
                @auth
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="ap-nav-item">
                            <i class="bi bi-arrow-right-square" aria-hidden="true"></i> Sign Out
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="ap-nav-item">
                        <i class="bi bi-box-arrow-in-right" aria-hidden="true"></i> Sign In
                    </a>
                @endauth
            </li>
        </ul>
    </nav>
</aside>
<div class="ap-overlay" id="apOverlay"></div>

{{-- ── Bar ──────────────────────────────────────────────────────── --}}
<header class="ap-topbar">
    <div class="ap-topbar-left">
        <button class="ap-icon-btn" id="apSidebarToggle" type="button" aria-label="Open menu" aria-controls="apSidebar" aria-expanded="false">
            <i class="bi bi-list" aria-hidden="true"></i>
        </button>
        <span class="ap-topbar-title">@yield('page-title', 'undilot')</span>
        <a href="{{ $apHome }}" class="ap-brand">
            <span class="ap-brand-icon" aria-hidden="true"><i class="bi bi-grid-3x3-gap-fill"></i></span>
            <span class="ap-brand-name">undilot</span>
        </a>
        <nav class="ap-topnav" aria-label="Main">
            @foreach($apNav as $apItem)
                @continue(! empty($apItem['drawer']))
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
                    <li>
                        <a class="dropdown-item" href="{{ route('profile') }}">
                            <i class="bi bi-person"></i> Profile
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
            {{-- Only where /auto is registered (local, testing, e2e) — never on a server. --}}
            @if(Route::has('dev.auto'))
                <a href="{{ route('dev.auto') }}" class="ap-signin ap-signin-dev">Quick sign in</a>
            @endif
            <a href="{{ route('login') }}" class="ap-signin">Sign In</a>
        @endauth
    </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // ── Rail and drawer ──
    // On a desk, signed in, the menu button hides and shows the fixed rail.
    // Otherwise it opens the drawer, which closes on its own button, a tap
    // outside, or Escape, and hands focus back to the menu button.
    const sidebar = document.getElementById('apSidebar');
    const overlay = document.getElementById('apOverlay');
    const toggle  = document.getElementById('apSidebarToggle');
    const desk    = window.matchMedia('(min-width: 992px)');
    const railed  = () => desk.matches && document.body.classList.contains('ap-with-rail');
    const syncExpanded = () => toggle.setAttribute('aria-expanded',
        String(railed() ? !document.body.classList.contains('ap-rail-hidden') : sidebar.classList.contains('open')));
    const isOpen  = () => sidebar.classList.contains('open');
    const openSidebar = () => {
        sidebar.classList.add('open');
        overlay.classList.add('show');
        document.documentElement.classList.add('ap-drawer-open');
        toggle.setAttribute('aria-expanded', 'true');
        (sidebar.querySelector('.ap-nav-item.active') || sidebar.querySelector('.ap-nav-item')).focus();
    };
    const closeSidebar = () => {
        if (!isOpen()) return;
        sidebar.classList.remove('open');
        overlay.classList.remove('show');
        document.documentElement.classList.remove('ap-drawer-open');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.focus();
    };
    toggle.addEventListener('click', () => {
        if (railed()) {
            document.body.classList.add('ap-rail-moving');
            document.body.classList.toggle('ap-rail-hidden');
            syncExpanded();
            return;
        }
        isOpen() ? closeSidebar() : openSidebar();
    });
    // Across the desk line: a drawer left open becomes the rail, and back.
    desk.addEventListener('change', () => {
        sidebar.classList.remove('open');
        overlay.classList.remove('show');
        document.documentElement.classList.remove('ap-drawer-open');
        syncExpanded();
    });
    syncExpanded();
    document.getElementById('apSidebarClose').addEventListener('click', closeSidebar);
    overlay.addEventListener('click', closeSidebar);
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeSidebar(); });

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
