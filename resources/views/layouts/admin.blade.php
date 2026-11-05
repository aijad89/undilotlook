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

    @include('partials.ui-tokens')

    <style>
        /* The office shell: a navy rail that holds still, a quiet white top bar,
           work on the grey ground. Gold marks where you are; nothing else. */
        :root {
            --adm-sidebar-w: 264px;
            /* Old names some views still read. */
            --adm-yellow: var(--ui-blue);
            --adm-yellow-hov: var(--ui-blue-hover);
            --adm-yellow-lt: var(--ui-blue-tint);
            --adm-yellow-md: var(--ui-blue-tint-2);
            --adm-bg: var(--ui-ground);
            --adm-text: var(--ui-text);
            --adm-text2: var(--ui-text-2);
            --adm-muted: var(--ui-muted);
            --adm-border: var(--ui-border);
            --adm-font: var(--ui-font);
        }

        body { background: var(--ui-ground); overflow-x: hidden; }

        /* ── Sidebar ─────────────────────────────── */
        #sidebar {
            position: fixed; inset: 0 auto 0 0; z-index: 1050;
            width: var(--adm-sidebar-w); height: 100vh; flex-shrink: 0;
            overflow-y: auto; overflow-x: hidden;
            background: var(--ui-navy-900); color: var(--ui-on-navy);
            transition: margin-left 0.3s var(--ui-ease);
            scrollbar-color: rgba(255, 255, 255, 0.2) transparent;
        }
        #sidebar::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.18); background-clip: padding-box; }
        body.sidebar-collapsed #sidebar { margin-left: calc(-1 * var(--adm-sidebar-w)); }

        #main-content { margin-left: var(--adm-sidebar-w); width: calc(100% - var(--adm-sidebar-w)); min-height: 100vh; transition: margin-left 0.3s var(--ui-ease), width 0.3s var(--ui-ease); }
        body.sidebar-collapsed #main-content { margin-left: 0; width: 100%; }

        #sidebar .brand { display: flex; align-items: center; gap: 12px; padding: 20px 20px 18px; border-bottom: 1px solid rgba(255, 255, 255, 0.08); }
        #sidebar .brand-mark {
            width: 36px; height: 36px; flex-shrink: 0; border-radius: 8px;
            display: grid; place-items: center; background: var(--ui-gold); color: var(--ui-navy-950);
            font-size: 1rem;
        }
        #sidebar .brand-name { display: block; font-size: 1rem; font-weight: 700; color: #fff; letter-spacing: -0.01em; line-height: 1.2; }
        #sidebar .brand-sub { display: block; font-size: 0.8125rem; color: var(--ui-on-navy-2); line-height: 1.3; }

        #sidebar .nav-section { padding: 20px 22px 6px; font-size: 0.75rem; font-weight: 600; color: var(--ui-on-navy-dim); letter-spacing: 0.02em; }

        #sidebar .nav-link, #sidebar .nav-item a {
            position: relative; display: flex; align-items: center; gap: 12px;
            margin: 2px 10px; padding: 9px 12px; min-height: 40px; border-radius: var(--ui-r);
            color: var(--ui-on-navy-3); font-size: 0.875rem; font-weight: 500; text-decoration: none;
            transition: background-color var(--ui-fast) var(--ui-ease), color var(--ui-fast) var(--ui-ease);
        }
        #sidebar .nav-link i, #sidebar .nav-item a i { width: 18px; flex-shrink: 0; text-align: center; font-size: 1rem; color: var(--ui-on-navy-icon); transition: color var(--ui-fast) var(--ui-ease); }
        #sidebar .nav-link:hover, #sidebar .nav-item a:hover { background: rgba(255, 255, 255, 0.07); color: #fff; }
        #sidebar .nav-link:hover i, #sidebar .nav-item a:hover i { color: #fff; }
        #sidebar .nav-link.active, #sidebar .nav-item > a.active { background: var(--ui-navy-700); color: #fff; font-weight: 600; }
        #sidebar .nav-link.active i, #sidebar .nav-item > a.active i { color: var(--ui-gold); }
        /* Gold is "you are here": a bar on the active item, nowhere else. */
        #sidebar .nav-link.active::before, #sidebar .nav-item > a.active::before {
            content: ""; position: absolute; left: -10px; top: 8px; bottom: 8px; width: 3px; border-radius: 0 3px 3px 0; background: var(--ui-gold);
        }

        #sidebar ul.submenu { list-style: none; margin: 2px 0 6px; padding: 0; display: none; }
        #sidebar .nav-item.with-sub.show ul.submenu { display: block; }
        #sidebar .submenu li a { margin: 1px 10px; padding: 8px 12px 8px 42px !important; min-height: 36px; font-size: 0.8125rem; color: var(--ui-on-navy-soft); }
        #sidebar .submenu li a.active { background: rgba(255, 255, 255, 0.08); color: #fff; font-weight: 600; }
        #sidebar .submenu li a.active::before { display: none; }
        #sidebar .nav-item.with-sub > a .chevron-icon { margin-left: auto; font-size: 0.75rem; color: var(--ui-on-navy-dim); transition: transform 0.2s var(--ui-ease); }
        #sidebar .nav-item.with-sub.show > a .chevron-icon { transform: rotate(180deg); }
        #sidebar .nav-item.with-sub > a.active { background: rgba(255, 255, 255, 0.05); }
        #sidebar .sidebar-badge { margin-left: auto; padding: 2px 7px; border-radius: 999px; background: var(--ui-bad); color: #fff; font-size: 0.75rem; font-weight: 700; line-height: 1.3; }
        #sidebar button.nav-link { width: calc(100% - 20px) !important; color: var(--ui-on-navy-soft); cursor: pointer; }
        #sidebar button.nav-link:hover { background: rgba(255, 255, 255, 0.07); color: #fff; }

        /* ── Top bar ─────────────────────────────── */
        .topbar {
            position: sticky; top: 0; z-index: 1030;
            display: flex; align-items: center; gap: 8px;
            height: 60px; padding: 0 28px;
            background: var(--ui-surface); border-bottom: 1px solid var(--ui-border);
        }
        .sidebar-toggle, .text-size-btn {
            width: 38px; height: 38px; display: grid; place-items: center; flex-shrink: 0;
            border: none; border-radius: var(--ui-r); background: none; color: var(--ui-muted); cursor: pointer;
            transition: background-color var(--ui-fast) var(--ui-ease), color var(--ui-fast) var(--ui-ease);
        }
        .sidebar-toggle:hover, .text-size-btn:hover { background: var(--ui-neutral-tint); color: var(--ui-text); }
        .text-size-btn.active { background: var(--ui-blue-tint); color: var(--ui-blue-text); }
        .topbar-user-btn {
            display: flex; align-items: center; gap: 10px; min-height: 40px; padding: 4px 12px 4px 4px;
            border: 1px solid var(--ui-border); border-radius: 999px; background: var(--ui-surface);
            color: var(--ui-text); font-size: 0.875rem; font-weight: 600; text-decoration: none;
            transition: background-color var(--ui-fast) var(--ui-ease), border-color var(--ui-fast) var(--ui-ease);
        }
        .topbar-user-btn:hover { background: var(--ui-surface-2); border-color: var(--ui-border-strong); color: var(--ui-text); }
        .topbar-user-btn::after { display: none; }
        .topbar-avatar { width: 30px; height: 30px; flex-shrink: 0; display: grid; place-items: center; border-radius: 50%; background: var(--ui-navy-900); color: #fff; font-size: 0.75rem; font-weight: 700; }

        /* ── Menus ───────────────────────────────── */
        .dropdown-menu { padding: 6px; border: 1px solid var(--ui-border); border-radius: var(--ui-r-lg); background: var(--ui-surface); box-shadow: var(--ui-shadow-pop); }
        .dropdown-menu .dropdown-item { padding: 8px 12px; border-radius: var(--ui-r-sm); color: var(--ui-text); font-size: 0.875rem; font-weight: 500; }
        .dropdown-menu .dropdown-item:hover { background: var(--ui-neutral-tint); color: var(--ui-text); }
        .dropdown-menu .dropdown-item.text-danger { color: var(--ui-bad-text) !important; }
        .dropdown-menu .dropdown-item.text-danger:hover { background: var(--ui-bad-tint); }
        .dropdown-menu .dropdown-divider { margin: 4px 0; border-color: var(--ui-sep); }

        /* Any Bootstrap .card a view still uses sits on the same surface. */
        .card { width: 100%; border: 1px solid var(--ui-border); border-radius: var(--ui-r-lg); background: var(--ui-surface); box-shadow: var(--ui-shadow-1); }

        /* ── Content frame ───────────────────────── */
        #content-main > .mac-page, .mac-page { max-width: none; }

        @media (max-width: 767.98px) {
            #sidebar { margin-left: calc(-1 * var(--adm-sidebar-w)); box-shadow: none; }
            body.sidebar-mobile-open #sidebar { margin-left: 0; box-shadow: var(--ui-shadow-pop); }
            #main-content { margin-left: 0; width: 100%; }
            .sidebar-overlay { display: none; position: fixed; inset: 0; z-index: 1040; background: rgba(7, 26, 51, 0.45); }
            body.sidebar-mobile-open .sidebar-overlay { display: block; }
            .topbar { padding: 0 12px; height: 56px; }
        }
        @media (min-width: 768px) { .sidebar-overlay { display: none !important; } }

        html.text-large { font-size: 112%; }
        html.text-xlarge { font-size: 124%; }

        .zoom-on-hover { max-width: 200px; max-height: 200px; cursor: zoom-in; transition: transform 0.25s var(--ui-ease); }
        .zoom-on-hover:hover { position: relative; z-index: 1000; transform: scale(2.5); }
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
            <span class="brand-mark" aria-hidden="true"><i class="bi bi-grid-3x3-gap-fill"></i></span>
            <span>
                <span class="brand-name">undilot</span>
                <span class="brand-sub">Admin Panel</span>
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
                    'label' => 'Reports',
                    'icon'  => 'download',
                    'route' => 'admin.reports.index',
                    'match' => ['admin.reports.*'],
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
                [
                    'label'    => 'Public Site',
                    'icon'     => 'globe',
                    'route'    => 'admin.footer-menus.index',
                    'match'    => ['admin.footer-menus.*', 'admin.social-links.*'],
                    'when'     => fn ($admin) => $admin->canManageSiteContent(),
                    'children' => [
                        ['label' => 'Footer pages', 'route' => 'admin.footer-menus.index', 'match' => 'admin.footer-menus.*'],
                        ['label' => 'Social links', 'route' => 'admin.social-links.index', 'match' => 'admin.social-links.*'],
                    ],
                ],
                [
                    'label' => 'Audit Trail',
                    'icon'  => 'journal-text',
                    'route' => 'admin.audit-trails.index',
                    'match' => ['admin.audit-trails.*'],
                    'when'  => fn ($admin) => $admin->hasGlobalAccess(),
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
            <button class="sidebar-toggle" id="sidebarToggle" type="button" title="Toggle sidebar" aria-label="Toggle sidebar">
                <i class="bi bi-list" style="font-size:1.5rem;" aria-hidden="true"></i>
            </button>

            <div class="ms-auto d-flex align-items-center gap-2">
            <button class="text-size-btn" id="adminTextSizeToggle" type="button" title="Enlarge text" aria-label="Enlarge text">
                <span style="font-size:1rem;font-weight:700;letter-spacing:-0.02em;" aria-hidden="true">Aa</span>
            </button>

            <div class="dropdown">
                @php
                    $adminUser = optional(Auth::guard('admin')->user());
                    $initials = collect(explode(' ', $adminUser->name ?? 'A'))->map(fn($w) => strtoupper(substr($w,0,1)))->take(2)->join('');
                @endphp
                <a class="topbar-user-btn dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                    <span class="topbar-avatar">{{ $initials }}</span>
                    {{ $adminUser->name ?? 'Admin' }}
                    <i class="bi bi-chevron-down" style="font-size:0.75rem;color:var(--ui-muted);"></i>
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
            textSizeBtn.setAttribute('aria-label', labels[level]);
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
