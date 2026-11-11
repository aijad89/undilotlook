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

    {{-- Apply enlarged-text class before paint to avoid flash --}}
    <script>(function(){var s=localStorage.getItem('textSizeLevel');if(s==='1')document.documentElement.classList.add('text-large');else if(s==='2')document.documentElement.classList.add('text-xlarge');})();</script>

    @include('partials.ui-tokens')

    <style>
        /* The public face: the portal's own bar (partials.portal-bar), so a usahawan who
           signs in steps into the same place, and the same footer (partials.site-footer). */
        :root {
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
    </style>
    @stack('styles')
</head>
<body @auth('web') class="ap-with-rail" @endauth>
@include('partials.skip-link')

@include('partials.portal-bar')

<main id="main-content">
{{-- Fallback heading for pages with no visible h1. Pages that render their own
     declare @section('own-heading') so this does not produce a second h1. --}}
@hasSection('own-heading')
@else
    <h1 class="visually-hidden">@yield('title', \App\Models\Setting::get('site_name', 'undilot'))</h1>
@endif
@yield('content')
</main>

@include('partials.site-footer')

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
@stack('scripts')
</body>
</html>
