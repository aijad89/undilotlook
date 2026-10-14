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
    @include('partials.ui-tokens')

    <style>
        /* The public face: the same navy bar as the portal, so a usahawan who
           signs in steps into the same place; a calm white footer. */
        :root {
            --pub-col: 1120px;
            --pub-blue: var(--ui-blue);
            --pub-blue-hov: var(--ui-blue-hover);
            --pub-bg: var(--ui-ground);
            --pub-text: var(--ui-text);
            --pub-text2: var(--ui-text-2);
            --pub-muted: var(--ui-muted);
            --pub-border: var(--ui-border);
            --pub-font: var(--ui-font);
            --pub-radius: var(--ui-r-lg);
        }
        html, body { background: var(--ui-ground); color: var(--ui-text); overflow-x: hidden; }
        /* A short page still ends at the bottom of the window. */
        body { min-height: 100vh; display: flex; flex-direction: column; }
        #main-content { flex: 1 0 auto; }

        /* ── Bar ─────────────────────────────────── */
        .pub-nav { position: sticky; top: 0; z-index: 1000; height: 64px; background: var(--ui-navy-900); }
        .pub-nav-inner { display: flex; align-items: center; gap: 8px; max-width: var(--pub-col); height: 100%; margin: 0 auto; padding: 0 24px; }
        .pub-brand { display: flex; align-items: center; gap: 10px; flex-shrink: 0; color: #fff; text-decoration: none; }
        .pub-brand:hover { color: #fff; }
        .pub-brand-icon, .pub-footer-icon { width: 34px; height: 34px; flex-shrink: 0; display: grid; place-items: center; border-radius: 9px; background: var(--ui-gold); color: var(--ui-navy-950); }
        .pub-brand-name { font-size: 1.1rem; font-weight: 700; letter-spacing: -0.01em; color: #fff; }
        .pub-nav-links { display: flex; align-items: center; gap: 4px; flex: 1; margin-left: 24px; }
        .pub-nav-links a { display: inline-flex; align-items: center; min-height: 40px; padding: 0 14px; border-radius: var(--ui-r); color: #d6deea; font-size: 0.95rem; font-weight: 600; text-decoration: none; }
        .pub-nav-links a:hover { background: rgba(255, 255, 255, 0.08); color: #fff; }
        .pub-nav-links a.active { color: #fff; box-shadow: inset 0 -3px 0 var(--ui-gold); border-radius: var(--ui-r) var(--ui-r) 0 0; }
        .pub-nav-actions { display: flex; align-items: center; gap: 8px; margin-left: auto; }
        .pub-btn-ghost, .pub-btn-primary {
            display: inline-flex; align-items: center; justify-content: center; gap: 6px;
            min-height: 42px; padding: 0 18px; border-radius: var(--ui-r);
            font-size: 0.95rem; font-weight: 600; text-decoration: none; cursor: pointer;
            transition: background-color var(--ui-fast) var(--ui-ease), color var(--ui-fast) var(--ui-ease);
        }
        .pub-btn-ghost { background: transparent; color: #fff; border: 1px solid rgba(255, 255, 255, 0.35); }
        .pub-btn-ghost:hover { background: rgba(255, 255, 255, 0.1); color: #fff; }
        .pub-btn-primary { background: #fff; color: var(--ui-navy-900); border: 1px solid #fff; }
        .pub-btn-primary:hover { background: var(--ui-blue-tint); color: var(--ui-navy-900); }
        .pub-user-btn { display: flex; align-items: center; gap: 8px; min-height: 44px; padding: 4px 12px 4px 4px; border: none; border-radius: 999px; background: transparent; color: #fff; font-size: 0.95rem; font-weight: 600; cursor: pointer; }
        .pub-user-btn:hover { background: rgba(255, 255, 255, 0.08); color: #fff; }
        .pub-avatar { width: 32px; height: 32px; flex-shrink: 0; display: grid; place-items: center; border-radius: 50%; background: #fff; color: var(--ui-navy-900); font-size: 0.75rem; font-weight: 700; }
        .pub-dropdown { min-width: 200px; padding: 6px; border: 1px solid var(--ui-border); border-radius: var(--ui-r-lg); background: var(--ui-surface); box-shadow: var(--ui-shadow-pop); }
        .pub-dropdown .dropdown-item { display: flex; align-items: center; gap: 10px; min-height: 44px; padding: 8px 12px; border-radius: var(--ui-r-sm); color: var(--ui-text); font-size: 0.95rem; }
        .pub-dropdown .dropdown-item:hover { background: var(--ui-neutral-tint); color: var(--ui-text); }
        .pub-dropdown .dropdown-item.text-danger { color: var(--ui-bad-text) !important; }
        .pub-dropdown hr { margin: 4px 0; border-color: var(--ui-sep); }

        /* ── Footer ──────────────────────────────── */
        .pub-footer-wrap { width: 100%; margin-top: 48px; flex-shrink: 0; }
        .pub-footer-panel { display: none; padding: 28px 24px; background: var(--ui-surface); border-top: 1px solid var(--ui-border); }
        .pub-footer-panel.show { display: block; }
        .pub-footer-panel-inner { max-width: var(--pub-col); margin: 0 auto; }
        .pub-footer-panel-title { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; font-size: 1.1rem; font-weight: 700; }
        .pub-footer-panel-close { width: 40px; height: 40px; border: none; border-radius: var(--ui-r); background: none; color: var(--ui-muted); }
        .pub-footer-panel-close:hover { background: var(--ui-neutral-tint); color: var(--ui-text); }
        .pub-footer-panel-body { max-width: 72ch; max-height: 260px; overflow-y: auto; color: var(--ui-text-2); line-height: 1.7; }
        .pub-footer { padding: 28px 24px 24px; background: var(--ui-surface); border-top: 1px solid var(--ui-border); }
        .pub-footer-inner { max-width: var(--pub-col); margin: 0 auto; }
        .pub-footer-top { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 18px; }
        .pub-footer-brand { display: flex; align-items: center; gap: 10px; color: var(--ui-text); font-weight: 700; text-decoration: none; }
        .pub-footer-copy { font-size: 0.9rem; color: var(--ui-muted); }
        .pub-footer-bottom { display: flex; align-items: flex-start; justify-content: space-between; gap: 24px; }
        .pub-footer-menu-rows { flex: 1; }
        .pub-footer-menu-row { display: flex; flex-wrap: wrap; gap: 4px 22px; margin-bottom: 4px; }
        .pub-footer-menu-btn { padding: 6px 0; border: none; background: none; color: var(--ui-text-2); font-size: 0.95rem; text-decoration: underline; text-underline-offset: 3px; cursor: pointer; }
        .pub-footer-menu-btn:hover, .pub-footer-menu-btn.active { color: var(--ui-blue-text); }
        .pub-footer-follow { flex-shrink: 0; text-align: right; }
        .pub-footer-follow-label { margin-bottom: 6px; font-size: 0.85rem; font-weight: 600; color: var(--ui-muted); }
        .pub-footer-follow-icons { display: flex; justify-content: flex-end; gap: 8px; }
        .pub-footer-follow-icons a { width: 44px; height: 44px; display: grid; place-items: center; border-radius: var(--ui-r); background: var(--ui-neutral-tint); color: var(--ui-text-2); font-size: 1.1rem; text-decoration: none; }
        .pub-footer-follow-icons a:hover { background: var(--ui-navy-900); color: #fff; }

        @media (max-width: 640px) {
            .pub-nav-inner { padding: 0 14px; }
            .pub-nav-links { margin-left: 8px; }
            .pub-brand-name { display: none; }
            .pub-footer-bottom { flex-direction: column; }
            .pub-footer-follow { text-align: left; }
            .pub-footer-follow-icons { justify-content: flex-start; }
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
            <div class="pub-brand-icon" aria-hidden="true"><i class="bi bi-grid-3x3-gap-fill"></i></div>
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
                    <div class="pub-footer-icon" aria-hidden="true"><i class="bi bi-grid-3x3-gap-fill"></i></div>
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
