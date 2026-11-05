{{--
    THE participant footer: the portal (layouts.frontend) and the public pages
    (layouts.public) both include it, so it never changes between them.

    Melon Ticket's measure: one row on the 1000px column, 64px below the content,
    as tall as the top bar (64px), small grey type. Left, the mark and the copyright; right,
    the office's footer pages (they open in a panel above the footer) and its
    social links. On a phone it stacks, centred.

    Kept short on purpose: both layouts are a full-height flex column whose <main>
    grows, so on a short page this sits at the bottom of the window — but only if
    content + 64px + this footer fits. A taller footer pushed it below the fold
    at 1440 × 900 with two events on the list.
--}}
<style>
    .site-footer-wrap { width: 100%; margin-top: 64px; flex-shrink: 0; }
    .site-footer-panel { display: none; padding: 28px 0; background: var(--ui-surface); border-top: 1px solid var(--ui-border); }
    .site-footer-panel.show { display: block; }
    .site-footer-panel-inner { max-width: var(--ui-page-max); margin: 0 auto; padding: 0 var(--ui-page-pad); }
    .site-footer-panel-title { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; font-size: 1.125rem; font-weight: 700; }
    .site-footer-panel-close { width: 40px; height: 40px; border: none; border-radius: var(--ui-r); background: none; color: var(--ui-muted); }
    .site-footer-panel-close:hover { background: var(--ui-neutral-tint); color: var(--ui-text); }
    .site-footer-panel-body { max-width: 72ch; max-height: 260px; overflow-y: auto; color: var(--ui-text-2); line-height: 1.7; }

    /* As tall as the top bar; it may grow only if the office adds more footer links than one row holds. */
    .site-footer { padding: 0; background: var(--ui-surface); border-top: 1px solid var(--ui-border); font-size: 0.875rem; color: var(--ui-muted); }
    .site-footer-inner { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px 24px; min-height: calc(var(--topbar-h) - 1px); max-width: var(--ui-page-max); margin: 0 auto; padding: 0 var(--ui-page-pad); }
    .site-footer-left { display: flex; flex-wrap: wrap; align-items: center; gap: 6px 12px; }
    .site-footer-brand { display: inline-flex; align-items: center; gap: 8px; color: var(--ui-text); font-weight: 700; text-decoration: none; }
    .site-footer-brand:hover { color: var(--ui-text); }
    .site-footer-icon { width: 24px; height: 24px; flex-shrink: 0; display: grid; place-items: center; border-radius: 6px; background: var(--ui-gold); color: var(--ui-navy-950); font-size: 0.75rem; }
    .site-footer-right, .site-footer-links { display: flex; flex-wrap: wrap; align-items: center; gap: 4px 18px; }
    .site-footer-menu-btn { padding: 6px 0; border: none; background: none; color: var(--ui-muted); font-size: 0.875rem; text-decoration: underline; text-underline-offset: 3px; cursor: pointer; }
    .site-footer-menu-btn:hover, .site-footer-menu-btn.active { color: var(--ui-blue-text); }
    .site-footer-social { display: flex; gap: 4px; }
    .site-footer-social a { width: 32px; height: 32px; display: grid; place-items: center; border-radius: var(--ui-r-sm); color: var(--ui-muted); text-decoration: none; }
    .site-footer-social a:hover { background: var(--ui-neutral-tint); color: var(--ui-text); }

    @media (max-width: 640px) {
        .site-footer-inner, .site-footer-left, .site-footer-right, .site-footer-links { flex-direction: column; justify-content: center; text-align: center; }
        .site-footer { padding: 20px 0; }
    }
</style>

@php
    $siteFooterMenus = \App\Models\FooterMenu::active()->orderBy('sort_order')->orderBy('id')->get();
    $siteSocialLinks = \App\Models\SocialLink::active()->orderBy('sort_order')->orderBy('id')->get();
@endphp

<div class="site-footer-wrap">
    {{-- One of the office's footer pages, opened from a link below --}}
    <div class="site-footer-panel" id="siteFooterPanel">
        <div class="site-footer-panel-inner">
            <div class="site-footer-panel-title">
                <span id="siteFooterPanelTitle"></span>
                <button class="site-footer-panel-close" onclick="closeSiteFooterPanel()" type="button" title="Close" aria-label="Close">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </div>
            <div class="site-footer-panel-body" id="siteFooterPanelBody"></div>
        </div>
    </div>

    <footer class="site-footer">
        <div class="site-footer-inner">
            <div class="site-footer-left">
                <a href="{{ route('home') }}" class="site-footer-brand">
                    <span class="site-footer-icon" aria-hidden="true"><i class="bi bi-grid-3x3-gap-fill"></i></span>
                    {{ \App\Models\Setting::get('site_name', 'undilot') }}
                </a>
                <span>&copy; {{ date('Y') }} Perbadanan Kemajuan Kraftangan Malaysia</span>
            </div>

            @if($siteFooterMenus->isNotEmpty() || $siteSocialLinks->isNotEmpty())
                <div class="site-footer-right">
                    @if($siteFooterMenus->isNotEmpty())
                        <nav class="site-footer-links" aria-label="Footer menu">
                            {{-- {{ }} escapes once, which the data attribute undoes; the
                                 content was cleaned to plain formatting when saved. --}}
                            @foreach($siteFooterMenus as $fm)
                                <button type="button" class="site-footer-menu-btn"
                                        data-title="{{ $fm->title }}"
                                        data-content="{{ $fm->content }}"
                                        onclick="toggleSiteFooterPanel(this)">{{ $fm->title }}</button>
                            @endforeach
                        </nav>
                    @endif
                    @if($siteSocialLinks->isNotEmpty())
                        <div class="site-footer-social">
                            @foreach($siteSocialLinks as $sl)
                                <a href="{{ $sl->url }}" target="_blank" rel="noopener" title="{{ $sl->label }}" aria-label="{{ $sl->label }}">
                                    @if($sl->icon_type === 'image' && $sl->icon_image)
                                        <img src="{{ $sl->icon_image_url }}" width="16" height="16" loading="lazy" style="object-fit:contain;" alt="">
                                    @else
                                        <i class="bi {{ $sl->icon_class ?: 'bi-globe' }}" aria-hidden="true"></i>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </footer>
</div>

<script>
    let activeSiteFooterBtn = null;

    function toggleSiteFooterPanel(btn) {
        const panel = document.getElementById('siteFooterPanel');
        if (activeSiteFooterBtn === btn && panel.classList.contains('show')) {
            closeSiteFooterPanel();
            return;
        }
        document.querySelectorAll('.site-footer-menu-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        activeSiteFooterBtn = btn;
        document.getElementById('siteFooterPanelTitle').textContent = btn.dataset.title;
        document.getElementById('siteFooterPanelBody').innerHTML    = btn.dataset.content;
        panel.classList.add('show');
        panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function closeSiteFooterPanel() {
        document.getElementById('siteFooterPanel').classList.remove('show');
        document.querySelectorAll('.site-footer-menu-btn').forEach(b => b.classList.remove('active'));
        activeSiteFooterBtn = null;
    }
</script>
