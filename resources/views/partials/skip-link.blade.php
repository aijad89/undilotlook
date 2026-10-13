{{-- Bypass Blocks (WCAG 2.4.1). Keyboard users otherwise tab the entire sidebar
     or navbar on every page load before reaching content — a real cost for
     admins working through dozens of registrations a day.

     Kept visually hidden until focused rather than display:none, because a
     display:none link is not focusable and would never activate.

     $target is overridable because the admin layout's sidebar-collapse CSS
     already owns #main-content; that layout passes 'content-main' instead. --}}
@php($skipTarget = $target ?? 'main-content')
<a href="#{{ $skipTarget }}" class="skip-link">Skip to main content</a>

<style>
    .skip-link {
        position: absolute;
        left: 8px;
        top: -100px;
        z-index: 2000;
        padding: 10px 18px;
        border-radius: 0 0 10px 10px;
        background: #1C1C1E;
        color: #fff;
        font-size: 0.85rem;
        font-weight: 600;
        text-decoration: none;
        transition: top 0.15s ease;
    }

    .skip-link:focus {
        top: 0;
        color: #fff;
        outline: 2px solid #fff;
        outline-offset: -4px;
    }

    @media (prefers-reduced-motion: reduce) {
        .skip-link { transition: none; }
    }
</style>
