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
        /* The usahawan's portal: the shared navy bar (partials.portal-bar), one
           reading column, large type and large targets. */
        :root {
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

        /* ── Page ────────────────────────────────── */
        /* A short page still ends at the bottom of the window: the body is a full-height
           column, the bar and footer keep their size, and the page between them grows. */
        body { min-height: 100vh; display: flex; flex-direction: column; }
        .ap-main { flex: 1 0 auto; display: flex; flex-direction: column; }
        .ap-content { flex: 1; width: 100%; max-width: var(--ui-page-max); margin: 0 auto; padding: 32px var(--ui-page-pad) 0; }

        @media (max-width: 576px) {
            .ap-content { padding-top: 20px; }
        }
    </style>

    @stack('styles')
</head>
<body>
@include('partials.skip-link')

@auth
@include('partials.portal-bar')

<div class="ap-main" id="apMain">
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

    @include('partials.site-footer')
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
</body>
</html>
