<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Dashboard')</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"
          integrity="sha384-XGjxtQfXaH2tnPFa9x+ruJTuLE3Aa6LhHSWRr1XeTyhezb4abCG4ccI5AkVDxqC+" crossorigin="anonymous">

    {{-- Apply enlarged-text class before paint to avoid flash --}}
    <script>(function(){var s=localStorage.getItem('textSizeLevel');if(s==='1')document.documentElement.classList.add('text-large');else if(s==='2')document.documentElement.classList.add('text-xlarge');})();</script>

    <style>
        :root {
            --adm-yellow:     #D97706;
            --adm-yellow-hov: #B45309;
            --adm-yellow-lt:  rgba(217, 119, 6, 0.10);
            --adm-yellow-md:  rgba(217, 119, 6, 0.18);
            --adm-bg:         #FAFAF8;
            --adm-sidebar-bg: #FFFFFF;
            --adm-text:       #1C1C1E;
            --adm-text2:      #374151;
            --adm-muted:      #6B7280;
            --adm-border:     rgba(0, 0, 0, 0.07);
            --adm-font:       -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'SF Pro Text', 'Helvetica Neue', sans-serif;
        }

        body {
            background-color: var(--adm-bg);
            overflow-x: hidden;
            font-family: var(--adm-font);
            color: var(--adm-text);
            -webkit-font-smoothing: antialiased;
        }

        /* ── Sidebar ─────────────────────────────── */
        #sidebar {
            width: 250px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background: var(--adm-sidebar-bg);
            border-right: 1px solid var(--adm-border);
            box-shadow: 2px 0 12px rgba(0,0,0,0.04);
            transition: margin-left 0.3s cubic-bezier(0.25,0.1,0.25,1);
            flex-shrink: 0;
            overflow-y: auto;
            overflow-x: hidden;
            z-index: 1050;
        }

        /* Sidebar scrollbar */
        #sidebar::-webkit-scrollbar { width: 5px; }
        #sidebar::-webkit-scrollbar-track { background: transparent; }
        #sidebar::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.08); border-radius: 3px; }
        #sidebar::-webkit-scrollbar-thumb:hover { background: rgba(0,0,0,0.14); }

        body.sidebar-collapsed #sidebar { margin-left: -250px; }

        #main-content {
            margin-left: 250px;
            width: calc(100% - 250px);
            transition: margin-left 0.3s cubic-bezier(0.25,0.1,0.25,1), width 0.3s cubic-bezier(0.25,0.1,0.25,1);
        }

        body.sidebar-collapsed #main-content {
            margin-left: 0;
            width: 100%;
        }

        /* Sidebar brand */
        #sidebar .brand {
            padding: 20px 20px 16px;
            border-bottom: 1px solid var(--adm-border);
        }

        #sidebar .brand-eyebrow {
            font-size: 0.67rem;
            font-weight: 700;
            color: var(--adm-muted);
            letter-spacing: 0.08em;
            text-transform: uppercase;
            display: block;
            margin-bottom: 2px;
        }

        #sidebar .brand-name {
            font-size: 1.05rem;
            font-weight: 800;
            color: var(--adm-text);
            letter-spacing: -0.02em;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        #sidebar .brand-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--adm-yellow);
            display: inline-block;
            flex-shrink: 0;
        }

        /* Sidebar section label */
        #sidebar .nav-section {
            font-size: 0.66rem;
            font-weight: 700;
            color: var(--adm-muted);
            letter-spacing: 0.07em;
            text-transform: uppercase;
            padding: 18px 20px 5px;
        }

        /* Sidebar nav links */
        #sidebar .nav-link,
        #sidebar .nav-item a {
            color: var(--adm-text2);
            padding: 8px 12px;
            margin: 1px 8px;
            border-radius: 9px;
            font-size: 0.845rem;
            font-weight: 500;
            transition: all 0.13s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            letter-spacing: -0.01em;
        }

        #sidebar .nav-link i,
        #sidebar .nav-item a i {
            color: var(--adm-muted);
            margin-right: 10px;
            width: 18px;
            text-align: center;
            font-size: 0.9rem;
            transition: color 0.13s;
            flex-shrink: 0;
        }

        #sidebar .nav-link:hover,
        #sidebar .nav-item a:hover {
            background: rgba(0,0,0,0.04);
            color: var(--adm-text);
        }

        #sidebar .nav-link:hover i,
        #sidebar .nav-item a:hover i {
            color: var(--adm-text2);
        }

        #sidebar .nav-link.active,
        #sidebar .nav-item a.active {
            background: var(--adm-yellow-lt);
            /* #D97706 (the brand accent used elsewhere against white/dark backgrounds) only
               reaches ~2.9:1 against this light amber-tint background — a dedicated darker
               shade is used here specifically to clear WCAG AA's 4.5:1 for normal text. */
            color: #92400E;
            font-weight: 600;
        }

        #sidebar .nav-link.active i,
        #sidebar .nav-item a.active i {
            color: #92400E;
        }

        /* Collapsible submenu */
        #sidebar ul.submenu {
            list-style: none;
            padding-left: 0;
            margin: 2px 0;
            display: none;
        }
        #sidebar .nav-item.with-sub.show ul.submenu { display: block; }
        #sidebar .submenu li a {
            padding-left: 46px !important;
            font-size: 0.81rem;
            margin: 0 8px;
            border-radius: 8px;
        }

        /* Sidebar chevron */
        #sidebar .nav-item.with-sub > a .chevron-icon {
            font-size: 0.65rem;
            transition: transform 0.2s ease;
            opacity: 0.4;
        }
        #sidebar .nav-item.with-sub.show > a .chevron-icon {
            transform: rotate(180deg);
        }

        /* Sidebar badge */
        #sidebar .sidebar-badge {
            background: #EF4444;
            color: #fff;
            font-size: 0.64rem;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 980px;
            margin-left: auto;
            line-height: 1.3;
        }

        /* Sidebar logout */
        #sidebar .sidebar-logout-btn {
            color: #9CA3AF;
            border: 1px solid rgba(0,0,0,0.08);
            border-radius: 10px;
            font-size: 0.82rem;
            font-weight: 500;
            padding: 8px 14px;
            margin: 0 8px;
            transition: all 0.13s;
            background: none;
            display: flex;
            align-items: center;
            width: calc(100% - 16px);
            text-align: left;
            cursor: pointer;
        }
        #sidebar .sidebar-logout-btn:hover {
            background: rgba(239,68,68,0.07);
            color: #EF4444;
            border-color: rgba(239,68,68,0.18);
        }
        #sidebar .sidebar-logout-btn i {
            margin-right: 10px;
            width: 18px;
            text-align: center;
            font-size: 0.9rem;
        }

        /* Sidebar footer divider */
        #sidebar .sidebar-footer-sep {
            border: none;
            border-top: 1px solid var(--adm-border);
            margin: 12px 8px 10px;
        }

        /* ── Topbar ──────────────────────────────── */
        .topbar {
            background: rgba(255, 255, 255, 0.90);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border-bottom: 1px solid var(--adm-border);
            position: sticky;
            top: 0;
            z-index: 1030;
            padding: 0 24px;
            height: 52px;
            display: flex;
            align-items: center;
        }

        .sidebar-toggle {
            background: none;
            border: none;
            border-radius: 8px;
            width: 34px;
            height: 34px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--adm-muted);
            transition: all 0.13s;
        }
        .sidebar-toggle:hover {
            background: rgba(0,0,0,0.04);
            color: var(--adm-text);
        }

        /* Keyboard focus for the layout chrome. mac-styles.blade.php carries
           the matching rule for in-page controls, but the sidebar and topbar
           render outside the content section, so they need their own — and use
           this layout's own --adm-yellow rather than the partial's token.

           (Do not write a Blade directive name here, even inside a CSS
           comment: Blade compiles the file before any CSS is parsed, so a
           literal section directive in this comment becomes a real
           startSection() call that opens an output buffer and never closes
           it. That is what AccessibilityBaselineTest's buffer check guards.)

           .sidebar-toggle in particular is transparent at rest, so an outline
           is used instead of a shadow that could vanish into the topbar. */
        .sidebar-toggle:focus-visible,
        .topbar-user-btn:focus-visible,
        .sidebar-logout-btn:focus-visible,
        #sidebar .nav-link:focus-visible,
        #sidebar .nav-item a:focus-visible {
            outline: 2px solid var(--adm-yellow);
            outline-offset: 2px;
        }

        /* Topbar user dropdown */
        .topbar-user-btn {
            background: rgba(0,0,0,0.04);
            border: 1px solid var(--adm-border);
            border-radius: 980px;
            padding: 4px 14px 4px 5px;
            font-size: 0.82rem;
            font-weight: 500;
            color: var(--adm-text);
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.13s;
            text-decoration: none;
            letter-spacing: -0.01em;
        }
        .topbar-user-btn:hover {
            background: rgba(0,0,0,0.07);
            color: var(--adm-text);
        }
        .topbar-avatar {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--adm-yellow), #F59E0B);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 0.7rem;
            font-weight: 700;
            flex-shrink: 0;
        }

        /* Dropdown menu */
        .dropdown-menu {
            border-radius: 12px;
            border: 1px solid var(--adm-border);
            box-shadow: 0 8px 32px rgba(0,0,0,0.10), 0 2px 8px rgba(0,0,0,0.05);
            padding: 6px;
            backdrop-filter: blur(20px);
            background: rgba(255,255,255,0.97);
        }
        .dropdown-menu .dropdown-item {
            border-radius: 8px;
            font-size: 0.82rem;
            font-weight: 500;
            padding: 7px 12px;
            color: var(--adm-text);
            transition: background 0.1s;
        }
        .dropdown-menu .dropdown-item:hover {
            background: var(--adm-yellow-lt);
            color: var(--adm-yellow);
        }
        .dropdown-menu .dropdown-item.text-danger:hover {
            background: rgba(239,68,68,0.08);
            color: #EF4444;
        }
        .dropdown-menu .dropdown-divider {
            margin: 4px 0;
            border-color: var(--adm-border);
        }

        /* ── Content ─────────────────────────────── */
        .card {
            border: 1px solid var(--adm-border);
            border-radius: 14px;
            width: 100%;
            background: rgba(255,255,255,0.85);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
        }

        /* ── Mobile ──────────────────────────────── */
        @media (max-width: 767.98px) {
            #sidebar { margin-left: -250px; }
            body.sidebar-mobile-open #sidebar { margin-left: 0; }
            #main-content { margin-left: 0; width: 100%; }
            .sidebar-overlay {
                display: none;
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,0.28);
                z-index: 1040;
                backdrop-filter: blur(4px);
            }
            body.sidebar-mobile-open .sidebar-overlay { display: block; }
            .topbar { padding: 0 16px; }
        }
        @media (min-width: 768px) {
            .sidebar-overlay { display: none !important; }
        }

        /* ── Enlarged text ───────────────────────── */
        html.text-large { font-size: 112%; }
        html.text-xlarge { font-size: 124%; }

        .text-size-btn {
            background: none;
            border: none;
            border-radius: 8px;
            width: 34px;
            height: 34px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--adm-muted);
            transition: all 0.13s;
        }
        .text-size-btn:hover { background: rgba(0,0,0,0.04); color: var(--adm-text); }
        .text-size-btn.active { background: var(--adm-yellow-lt); color: var(--adm-yellow); }

        /* ── Utilities ───────────────────────────── */
        .zoom-on-hover {
            transition: transform 0.25s ease;
            cursor: zoom-in;
            max-width: 200px;
            max-height: 200px;
        }
        .zoom-on-hover:hover {
            transform: scale(2.5);
            z-index: 1000;
            position: relative;
        }
    </style>

    @stack('styles')
</head>
<body>
@include('partials.skip-link', ['target' => 'content-main'])

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="d-flex">
    <!-- Sidebar -->
    <div id="sidebar">
        <nav aria-label="Admin sidebar navigation">
        <div class="brand">
            <span class="brand-eyebrow">Admin Panel</span>
            <span class="brand-name">
                <span class="brand-dot"></span>
                undilot
            </span>
        </div>

        @php
            $authAdmin = auth()->guard('admin')->user();

            /*
             * THE ONE PLACE A SIDEBAR ENTRY IS DECLARED.
             *
             * The system this came from spelled every link out in the markup —
             * twenty-two `route()` calls spread over a hundred and sixty lines of
             * nested @if. That shape has one failure mode and it is severe: a route
             * removed anywhere in the application throws RouteNotFoundException out
             * of the LAYOUT, so every admin screen dies at once and the stack trace
             * names the layout rather than the thing that was deleted.
             *
             * A list cannot prevent that, but it puts the whole surface in one
             * screenful where a reader can see it, and it makes adding a module one
             * line instead of fifteen.
             *
             * Each item:
             *   label     what the link says
             *   icon      Bootstrap Icons name, without the `bi-`
             *   route     the route name to link to
             *   match     route patterns that light this item up (defaults to `route`)
             *   when      a closure taking the signed-in admin; absent means everyone
             *   section   a heading rendered ABOVE this item
             *   children  a submenu of [label, route, match?] rows
             */
            $nav = [
                [
                    'label' => 'Dashboard',
                    'icon'  => 'square-half',
                    'route' => 'admin.dashboard',
                ],
                [
                    'section' => 'The draw',
                    'label'   => 'Events',
                    'icon'    => 'calendar-event',
                    'route'   => 'admin.events.index',
                    // The draw list lives under an event, so it lights this up too.
                    'match'   => ['admin.events.*'],
                ],
                [
                    'label' => 'Lot Draw',
                    'icon'  => 'grid-3x3-gap',
                    'route' => 'admin.polling.index',
                    'match' => ['admin.polling.*'],
                ],
                [
                    'label' => 'Companies',
                    'icon'  => 'building',
                    'route' => 'admin.companies.index',
                    'match' => ['admin.companies.*'],
                ],
                [
                    'label' => 'Announcements',
                    'icon'  => 'megaphone',
                    'route' => 'admin.announcements.index',
                    'match' => ['admin.announcements.*'],
                ],
                [
                    'section'  => 'Settings',
                    'label'    => 'Dropdown Lists',
                    'icon'     => 'sliders',
                    'route'    => 'admin.settings.lists',
                    'match'    => ['admin.settings.*'],
                    // National lists. A state admin editing one would be editing
                    // thirteen other zones' vocabulary.
                    'when'     => fn ($admin) => $admin->hasGlobalAccess(),
                    'children' => [
                        ['label' => 'Zones & sizes', 'route' => 'admin.settings.lists'],
                        ['label' => 'Craft Category Code', 'route' => 'admin.settings.kraf-codes'],
                        ['label' => 'Craft Hierarchy', 'route' => 'admin.settings.kraf-hierarchy'],
                    ],
                ],
            ];

            $visible = array_values(array_filter(
                $nav,
                fn ($item) => ! isset($item['when']) || $item['when']($authAdmin)
            ));

            $isActive = function (array $item) {
                foreach ((array) ($item['match'] ?? $item['route'] ?? []) as $pattern) {
                    if (request()->routeIs($pattern)) {
                        return true;
                    }
                }

                return false;
            };
        @endphp

        <ul class="nav flex-column mt-1">
            @foreach($visible as $item)
                @if(! empty($item['section']))
                    <li class="nav-section">{{ $item['section'] }}</li>
                @endif

                @php $active = $isActive($item); @endphp

                @if(empty($item['children']))
                    <li class="nav-item">
                        <a class="nav-link {{ $active ? 'active' : '' }}" href="{{ route($item['route']) }}">
                            <i class="bi bi-{{ $item['icon'] }}"></i> {{ $item['label'] }}
                        </a>
                    </li>
                @else
                    <li class="nav-item with-sub {{ $active ? 'show' : '' }}">
                        <a class="nav-link {{ $active ? 'active' : '' }}" href="#">
                            <i class="bi bi-{{ $item['icon'] }}"></i> {{ $item['label'] }}
                            <i class="bi bi-chevron-down ms-auto chevron-icon"></i>
                        </a>
                        <ul class="submenu">
                            @foreach($item['children'] as $child)
                                <li>
                                    <a class="{{ $isActive($child) ? 'active' : '' }}" href="{{ route($child['route']) }}">
                                        {{ $child['label'] }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </li>
                @endif
            @endforeach

            <li class="nav-item mt-2">
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="nav-link border-0 bg-transparent w-100 text-start">
                        <i class="bi bi-arrow-right-square"></i> Sign Out
                    </button>
                </form>
            </li>
        </ul>
        </nav>
    </div>

    <!-- Main Content -->
    <div id="main-content">
        <nav class="topbar" aria-label="Admin top bar">
            <button class="sidebar-toggle" id="sidebarToggle" type="button" title="Toggle sidebar">
                <i class="bi bi-sidebar-fill" style="font-size:1rem;"></i>
            </button>

            <div class="ms-auto d-flex align-items-center gap-2">
            <button class="text-size-btn" id="adminTextSizeToggle" type="button" title="Enlarge text">
                <i class="bi bi-fonts" style="font-size:1rem;"></i>
            </button>

            <div class="dropdown">
                @php
                    $adminUser = optional(Auth::guard('admin')->user());
                    $initials = collect(explode(' ', $adminUser->name ?? 'A'))->map(fn($w) => strtoupper(substr($w,0,1)))->take(2)->join('');
                @endphp
                <a class="topbar-user-btn dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                    <span class="topbar-avatar">{{ $initials }}</span>
                    {{ $adminUser->name ?? 'Admin' }}
                    <i class="bi bi-chevron-down" style="font-size:0.58rem;color:#9CA3AF;"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <form method="POST" action="{{ route('admin.logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger" style="width:100%;text-align:left;">
                                <i class="bi bi-arrow-right-square me-2"></i> Sign Out
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
            </div>
        </nav>

        {{-- id is "content-main", not "main-content": the sidebar-collapse CSS above
             already owns #main-content on the wrapper div at line 570. --}}
        <main id="content-main" class="content-wrapper" style="min-height: calc(100vh - 52px);">
            <h1 class="visually-hidden">@yield('title', 'Admin Dashboard')</h1>
            <div class="p-4">
                @yield('content')
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const body    = document.body;
    const toggle  = document.getElementById('sidebarToggle');
    const overlay = document.getElementById('sidebarOverlay');
    const isMobile = () => window.innerWidth < 768;

    toggle.addEventListener('click', function () {
        if (isMobile()) {
            body.classList.toggle('sidebar-mobile-open');
        } else {
            body.classList.toggle('sidebar-collapsed');
        }
    });

    overlay.addEventListener('click', function () {
        body.classList.remove('sidebar-mobile-open');
    });

    document.querySelectorAll('#sidebar .with-sub > a').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            this.closest('.with-sub').classList.toggle('show');
        });
    });

    // .table-responsive only becomes an actually-scrollable region on narrow viewports where
    // table content overflows — at that point it needs to be keyboard-focusable (WCAG 2.1.1 /
    // axe's scrollable-region-focusable), since a mouse/touch-only scroll target is unreachable
    // for keyboard users.
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

    // ── Text size toggle (3 levels: 0=normal, 1=large, 2=xlarge) ──
    const textSizeBtn = document.getElementById('adminTextSizeToggle');
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

/*
 * CONFIRM DIALOGS, WITHOUT PUTTING THE VALUE IN FRONT OF A JAVASCRIPT PARSER.
 *
 * Twelve buttons in this panel built a JavaScript string literal out of a database
 * value inside an onclick attribute — a Blade echo of $user->name dropped straight
 * between two single quotes inside confirm(...).
 *
 * A Blade echo does not protect that. It runs e(), which turns ' into &#039;
 * — correct for an HTML attribute and useless here, because the browser DECODES
 * the attribute before the JavaScript parser sees it. Measured:
 *
 *     stored : x');alert(document.cookie);//
 *     source : onclick="return confirm('Block x&#039;);alert(...);// …')"
 *     JS sees: return confirm('Block x');alert(document.cookie);// …')
 *
 * One of those twelve carried `users.name`, which the usahawan edits on their own
 * profile and an admin's browser then executes.
 *
 * THE VALUE NOW TRAVELS AS DATA, NOT AS CODE. `data-confirm` is read with
 * `dataset`, so `e()` is doing the job it is actually for and no JavaScript parser
 * is ever handed the string. That is the shape
 *
 * Capture phase, so this runs before anything a page binds later, and
 * stopPropagation so a declined confirm cancels the rest of the click the way
 * `return false` from an inline handler used to.
 */
document.addEventListener('click', function (e) {
    const el = e.target.closest('[data-confirm]');

    if (el && ! window.confirm(el.dataset.confirm)) {
        e.preventDefault();
        e.stopPropagation();
    }
}, true);
</script>

@stack('scripts')
</body>
</html>
