<style>
/* ══════════════════════════════════════════════════════
   Apple UI Design System — User Portal Components
   Include at the top of the content section in user views.
   Never spell a Blade directive out in here — Blade compiles them inside CSS
   comments too, and this line once left an unclosed section on every page.
   ══════════════════════════════════════════════════════ */

/* ── Tokens ────────────────────────────────────────────
   Mirrors the :root layer mac-styles.blade.php already has, so a future
   brand pass has one place to change instead of ~90 literals. Values are
   unchanged from what shipped before, with one exception noted at
   --ap-placeholder. */
:root {
    /* Text */
    --ap-text:        #1C1C1E;
    --ap-text2:       #3A3A3C;
    --ap-muted:       #6E6E73;
    /* Placeholder was #AEAEB2, which measured 2.06:1 against the field's own
       background — under half of WCAG 1.4.3 AA. --ap-muted measures 5.07:1
       and was already in this system. */
    --ap-placeholder: var(--ap-muted);

    /* Accent */
    --ap-accent:      #007AFF;
    --ap-accent-tx:   #0066DD;  /* accent darkened to carry text/fills at AA */
    --ap-accent-hv:   #0052B4;

    /* Semantic hues */
    --ap-green:       #34C759;
    --ap-orange:      #FF9500;
    --ap-red:         #FF3B30;
    --ap-purple:      #5856D6;
    --ap-teal:        #0099FF;
    --ap-purple-alt:  #AF52DE;

    /* Semantic text-on-tint (all ≥4.5:1 on their own tinted background) */
    --ap-green-tx:    #166534;
    --ap-orange-tx:   #92400E;
    --ap-red-tx:      #B91C1C;
    --ap-purple-tx:   #5B21B6;
    --ap-gray-tx:     #636366;
    --ap-blue-tx:     #0052B4;

    /* Alert text */
    --ap-alert-green:  #1A7A30;
    --ap-alert-red:    #C0291F;
    --ap-alert-orange: #965A00;
    --ap-alert-blue:   #005ED8;

    /* Surfaces & lines */
    --ap-surface:     #FFFFFF;
    --ap-surface-alt: rgba(242,242,247,0.5);
    --ap-border:      rgba(60,60,67,0.1);
    --ap-border-soft: rgba(60,60,67,0.07);
    --ap-sep:         #C7C7CC;
    --ap-field-bg:    rgba(118,118,128,0.06);

    /* Type */
    --ap-font: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'SF Pro Text', 'Helvetica Neue', Arial, sans-serif;
}

/* ── Page wrapper ──────────────────────────────────── */
.ap-page {
    font-family: var(--ap-font);
    -webkit-font-smoothing: antialiased;
}

/* ── Page header ───────────────────────────────────── */
.ap-page-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 20px;
}

.ap-page-title {
    font-size: 1.55rem;
    font-weight: 700;
    color: var(--ap-text);
    letter-spacing: -0.03em;
    line-height: 1.2;
    margin: 0 0 3px;
}

.ap-page-subtitle {
    font-size: 0.845rem;
    color: var(--ap-muted);
    margin: 0;
}

.ap-page-actions {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
}

/* ── Breadcrumb ────────────────────────────────────── */
.ap-breadcrumb {
    font-size: 0.78rem;
    color: var(--ap-muted);
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.ap-breadcrumb a { color: var(--ap-accent-tx); text-decoration: underline; }
.ap-breadcrumb a:hover { text-decoration: underline; }
/* Purely decorative separator glyph; the crumb structure reads without it. */
.ap-breadcrumb .sep { color: var(--ap-sep); font-size: 0.65rem; }

/* ── Cards ─────────────────────────────────────────── */
.ap-card {
    background: var(--ap-surface);
    border: 1px solid var(--ap-border);
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 16px rgba(0,0,0,0.05);
    transition: box-shadow 0.18s;
}

.ap-card:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.06), 0 8px 28px rgba(0,0,0,0.07);
}

.ap-card-body { padding: 20px 22px; }
.ap-card-body-sm { padding: 14px 18px; }
.ap-card-body-lg { padding: 28px 30px; }

.ap-card-header {
    padding: 14px 22px 12px;
    border-bottom: 1px solid rgba(60, 60, 67, 0.08);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}

.ap-card-header-title {
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--ap-text);
    letter-spacing: -0.01em;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.ap-card-footer {
    padding: 12px 22px;
    border-top: 1px solid rgba(60, 60, 67, 0.08);
    background: var(--ap-surface-alt);
}

/* ── Stat card ─────────────────────────────────────── */
.ap-stat-card {
    background: var(--ap-surface);
    border: 1px solid var(--ap-border);
    border-radius: 14px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 14px rgba(0,0,0,0.04);
    transition: transform 0.15s, box-shadow 0.15s;
    height: 100%;
}

.ap-stat-card:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

.ap-stat-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
    margin-bottom: 14px;
}

.ap-stat-icon.blue   { background: rgba(0,122,255,0.1);   color: var(--ap-accent); }
.ap-stat-icon.purple { background: rgba(88,86,214,0.1);   color: var(--ap-purple); }
.ap-stat-icon.green  { background: rgba(52,199,89,0.1);   color: var(--ap-green); }
.ap-stat-icon.orange { background: rgba(255,149,0,0.1);   color: var(--ap-orange); }
.ap-stat-icon.red    { background: rgba(255,59,48,0.1);   color: var(--ap-red); }
.ap-stat-icon.teal   { background: rgba(90,200,250,0.12); color: var(--ap-teal); }

.ap-stat-value {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--ap-text);
    letter-spacing: -0.03em;
    line-height: 1;
    margin-bottom: 4px;
}

.ap-stat-label {
    font-size: 0.82rem;
    color: var(--ap-muted);
    font-weight: 500;
}

/* ── Buttons ───────────────────────────────────────── */
.ap-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    border-radius: 980px;
    padding: 8px 18px;
    font-size: 0.845rem;
    font-weight: 500;
    letter-spacing: -0.01em;
    border: none;
    cursor: pointer;
    transition: all 0.15s ease;
    text-decoration: none;
    line-height: 1;
    white-space: nowrap;
    /* WCAG 2.5.8 (AA) requires a 24×24 CSS px target. line-height:1 plus tight
       padding otherwise lands .ap-btn-sm at ~23px. */
    min-height: 32px;
}

.ap-btn:focus-visible {
    outline: none;
    box-shadow: 0 0 0 3px rgba(0,122,255,0.3);
}

.ap-btn-primary {
    background: var(--ap-accent-tx);
    color: #fff;
}
.ap-btn-primary:hover { background: var(--ap-accent-hv); color: #fff; transform: scale(1.02); }

.ap-btn-secondary {
    background: rgba(60,60,67,0.1);
    color: var(--ap-text);
}
.ap-btn-secondary:hover { background: rgba(60,60,67,0.16); color: var(--ap-text); }

.ap-btn-outline {
    background: transparent;
    color: var(--ap-accent-tx);
    border: 1.5px solid var(--ap-accent);
}
.ap-btn-outline:hover { background: rgba(0,122,255,0.08); color: var(--ap-accent-tx); }

.ap-btn-danger {
    background: rgba(255,59,48,0.1);
    color: var(--ap-red-tx);
    border: 1px solid rgba(255,59,48,0.18);
}
.ap-btn-danger:hover { background: var(--ap-red-tx); color: #fff; }

.ap-btn-success {
    background: rgba(52,199,89,0.12);
    color: var(--ap-green-tx);
    border: 1px solid rgba(52,199,89,0.2);
}
.ap-btn-success:hover { background: var(--ap-green-tx); color: #fff; }

/* Stays visually small; min-height keeps the hit target legal. */
.ap-btn-sm { padding: 5px 13px; font-size: 0.8rem; min-height: 26px; }
.ap-btn-lg { padding: 11px 24px; font-size: 0.92rem; min-height: 40px; }

/* ── Form inputs ───────────────────────────────────── */
.ap-input, .ap-select, .ap-textarea {
    width: 100%;
    background: var(--ap-field-bg);
    border: 1px solid rgba(60,60,67,0.12);
    border-radius: 10px;
    padding: 9px 13px;
    font-size: 0.87rem;
    color: var(--ap-text);
    font-family: var(--ap-font);
    transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
    outline: none;
    appearance: none;
    -webkit-appearance: none;
}

.ap-input:focus, .ap-select:focus, .ap-textarea:focus {
    background: #fff;
    border-color: var(--ap-accent);
    box-shadow: 0 0 0 3px rgba(0,122,255,0.18);
}

.ap-input::placeholder, .ap-textarea::placeholder { color: var(--ap-placeholder); }

.ap-label {
    font-size: 0.82rem;
    font-weight: 600;
    color: var(--ap-text2);
    margin-bottom: 5px;
    display: block;
    letter-spacing: -0.005em;
}

.ap-form-group { margin-bottom: 16px; }

.ap-filter-bar {
    background: rgba(255,255,255,0.8);
    backdrop-filter: blur(20px) saturate(180%);
    -webkit-backdrop-filter: blur(20px) saturate(180%);
    border: 1px solid var(--ap-border);
    border-radius: 14px;
    padding: 14px 18px;
    margin-bottom: 18px;
}

/* ── Table ─────────────────────────────────────────── */
.ap-table { width: 100%; border-collapse: separate; border-spacing: 0; }

.ap-table thead th {
    background: rgba(242,242,247,0.7);
    padding: 10px 16px;
    font-size: 0.73rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--ap-muted);
    border-bottom: 1px solid var(--ap-border);
    white-space: nowrap;
}

.ap-table tbody td {
    padding: 13px 16px;
    font-size: 0.87rem;
    color: var(--ap-text);
    border-bottom: 1px solid rgba(60,60,67,0.06);
    vertical-align: middle;
}

.ap-table tbody tr:last-child td { border-bottom: none; }

.ap-table tbody tr:hover td {
    background: rgba(0,122,255,0.025);
}

/* ── Status badges ─────────────────────────────────── */
.ap-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 10px;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: -0.01em;
    white-space: nowrap;
}

.ap-badge::before {
    content: '';
    width: 6px;
    height: 6px;
    border-radius: 50%;
    flex-shrink: 0;
    background: currentColor;
    opacity: 0.7;
}

.ap-badge-green  { background: rgba(52,199,89,0.12);   color: var(--ap-green-tx); }
.ap-badge-blue   { background: rgba(0,122,255,0.1);    color: var(--ap-blue-tx); }
.ap-badge-orange { background: rgba(255,149,0,0.12);   color: var(--ap-orange-tx); }
.ap-badge-red    { background: rgba(255,59,48,0.1);    color: var(--ap-red-tx); }
.ap-badge-purple { background: rgba(175,82,222,0.1);   color: var(--ap-purple-tx); }
.ap-badge-gray   { background: rgba(142,142,147,0.12); color: var(--ap-gray-tx); }

/* ── Alert / Flash ─────────────────────────────────── */
.ap-alert {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 13px 16px;
    border-radius: 12px;
    font-size: 0.865rem;
    margin-bottom: 16px;
}

.ap-alert-icon { font-size: 1rem; flex-shrink: 0; margin-top: 1px; }

.ap-alert-success { background: rgba(52,199,89,0.1);  border: 1px solid rgba(52,199,89,0.2);  color: var(--ap-alert-green); }
.ap-alert-danger  { background: rgba(255,59,48,0.08); border: 1px solid rgba(255,59,48,0.15); color: var(--ap-alert-red); }
.ap-alert-warning { background: rgba(255,149,0,0.09); border: 1px solid rgba(255,149,0,0.18); color: var(--ap-alert-orange); }
.ap-alert-info    { background: rgba(0,122,255,0.07); border: 1px solid rgba(0,122,255,0.14); color: var(--ap-alert-blue); }

/* ── Section title ─────────────────────────────────── */
.ap-section-title {
    font-size: 0.78rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.055em;
    color: var(--ap-muted);
    margin-bottom: 10px;
    margin-top: 6px;
}

/* ── Divider ───────────────────────────────────────── */
.ap-divider {
    border: none;
    border-top: 1px solid var(--ap-border);
    margin: 16px 0;
}

/* ── Empty state ───────────────────────────────────── */
.ap-empty {
    text-align: center;
    padding: 44px 24px;
    color: var(--ap-muted);
}

.ap-empty-icon {
    font-size: 2.5rem;
    display: block;
    margin-bottom: 12px;
    opacity: 0.45;
}

.ap-empty-title {
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--ap-text2);
    margin-bottom: 5px;
}

.ap-empty-text {
    font-size: 0.83rem;
    color: var(--ap-muted);
    margin-bottom: 16px;
}

/* ── Progress bar ──────────────────────────────────── */
.ap-progress {
    height: 6px;
    border-radius: 999px;
    background: rgba(60,60,67,0.1);
    overflow: hidden;
}

/* Deliberately not animated. The only caller (dashboard.blade.php) sets the
   bar length server-side at render and no JS ever changes it, so the old
   animation could never fire — it was a layout-property tween for nothing. */
.ap-progress-bar {
    height: 100%;
    border-radius: 999px;
    background: var(--ap-accent);
}

.ap-progress-bar.green  { background: var(--ap-green); }
.ap-progress-bar.orange { background: var(--ap-orange); }
.ap-progress-bar.red    { background: var(--ap-red); }

/* ── Detail grid (label / value rows) ─────────────── */
.ap-detail-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 10px 0;
    border-bottom: 1px solid var(--ap-border-soft);
    gap: 12px;
    font-size: 0.87rem;
}

.ap-detail-row:last-child { border-bottom: none; }

.ap-detail-label {
    color: var(--ap-muted);
    font-weight: 500;
    flex-shrink: 0;
    min-width: 120px;
}

.ap-detail-value {
    color: var(--ap-text);
    font-weight: 500;
    text-align: right;
}

/* ── List group (Apple-style) ──────────────────────── */
.ap-list { list-style: none; padding: 0; margin: 0; }

.ap-list-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 11px 0;
    border-bottom: 1px solid var(--ap-border-soft);
    font-size: 0.875rem;
    color: var(--ap-text);
}

.ap-list-item:last-child { border-bottom: none; }

/* ── Timeline ──────────────────────────────────────── */
.ap-timeline { padding-left: 18px; border-left: 2px solid var(--ap-border); }

.ap-timeline-item {
    position: relative;
    padding-bottom: 18px;
    padding-left: 14px;
}

.ap-timeline-item:last-child { padding-bottom: 0; }

.ap-timeline-dot {
    position: absolute;
    left: -24px;
    top: 3px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px currentColor;
    background: currentColor;
}

.ap-timeline-dot.green  { color: var(--ap-green); }
.ap-timeline-dot.blue   { color: var(--ap-accent); }
.ap-timeline-dot.orange { color: var(--ap-orange); }
.ap-timeline-dot.red    { color: var(--ap-red); }
.ap-timeline-dot.gray   { color: var(--ap-sep); }

.ap-timeline-label {
    font-size: 0.845rem;
    font-weight: 600;
    color: var(--ap-text);
    margin-bottom: 2px;
}

.ap-timeline-desc {
    font-size: 0.78rem;
    color: var(--ap-muted);
}

/* ── Muted text ────────────────────────────────────── */
.ap-muted { color: var(--ap-muted); font-size: 0.83rem; }

/* ── Responsive helpers ────────────────────────────── */
@media (max-width: 576px) {
    .ap-page-header { flex-direction: column; }
    .ap-detail-row  { flex-direction: column; }
    .ap-detail-value { text-align: left; }
}

/* ── Reduced motion ────────────────────────────────────
   Targeted, not a blanket 0.01ms kill: spatial transforms are what trigger
   vestibular symptoms, so those are dropped. Colour, background, shadow and
   focus-ring transitions stay — they carry state feedback the user still
   needs, and none of them move anything on screen. */
@media (prefers-reduced-motion: reduce) {
    .ap-btn-primary:hover,
    .ap-stat-card:hover {
        transform: none;
    }
    .ap-stat-card,
    .ap-btn {
        transition-property: background-color, border-color, color, box-shadow;
    }
}

/* ── Section heading ───────────────────────────────── */
/* A page of equal-weight cards has no reading order. These name the three
   groups a company profile actually splits into, and carry more space above
   than below so the heading belongs to what follows it. */
.ap-section-head { margin: 34px 0 12px; }
.ap-section-title {
    font-size: 1.05rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    color: var(--ap-text);
    margin: 0;
}

/* ── Identity block ────────────────────────────────── */
.ap-identity-logo {
    width: 88px; height: 88px; flex-shrink: 0;
    border-radius: 14px;
    overflow: hidden;
    background: var(--ap-surface-alt);
    border: 1px solid var(--ap-border);
    display: flex; align-items: center; justify-content: center;
}
.ap-identity-logo img { width: 100%; height: 100%; object-fit: cover; }
.ap-identity-logo i { font-size: 2.2rem; color: #C8C8D0; }

.ap-identity-name {
    font-size: 1.35rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    color: var(--ap-text);
    margin: 0 0 8px;
}

/* Facts the office owns: read-only, so they are a definition list rather than
   a form. auto-fit keeps the column count honest at every width instead of
   locking a grid the content has to fit. */
.ap-factgrid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(13.5rem, 1fr));
    gap: 12px 20px;
    padding-top: 18px;
    border-top: 1px solid var(--ap-border);
}
.ap-factgrid dt {
    font-size: 0.72rem;
    font-weight: 600;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: var(--ap-muted);
    margin-bottom: 2px;
}
.ap-factgrid dd {
    font-size: 0.87rem;
    color: var(--ap-text);
    margin: 0;
}

/* ── Premise tabs ──────────────────────────────────── */
/* Five premises of the same shape. Stacked, they were five screens of near
   identical markup; here the set is visible at once and the dot says which
   are filled without opening any of them. */
.ap-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 2px;
    padding: 6px 8px 0;
    border-bottom: 1px solid var(--ap-border);
    background: var(--ap-surface-alt);
    list-style: none;
    margin: 0;
}
.ap-tabs .nav-link {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    border: 0;
    border-radius: 10px 10px 0 0;
    padding: 9px 14px;
    font-size: 0.845rem;
    font-weight: 500;
    color: var(--ap-muted);
    background: transparent;
    transition: background-color 0.15s, color 0.15s;
}
.ap-tabs .nav-link i { font-size: 0.9rem; }
.ap-tabs .nav-link:hover { background: rgba(60,60,67,0.06); color: var(--ap-text); }
.ap-tabs .nav-link.active {
    color: var(--ap-accent-tx);
    font-weight: 600;
    background: var(--ap-surface);
    box-shadow: inset 0 -2px 0 var(--ap-accent-tx);
}
.ap-tabs .nav-link:focus-visible {
    outline: 2px solid var(--ap-accent-tx);
    outline-offset: -2px;
}

/* Filled or empty, at a glance. Never the only carrier — the tab also ships a
   visually-hidden "(filled in)" / "(empty)". */
.ap-tab-dot {
    width: 7px; height: 7px;
    border-radius: 999px;
    background: rgba(60,60,67,0.22);
}
.ap-tab-dot.is-filled { background: var(--ap-green); }

@media (max-width: 575.98px) {
    .ap-identity-logo { width: 64px; height: 64px; border-radius: 12px; }
    .ap-identity-logo i { font-size: 1.6rem; }
    .ap-identity-name { font-size: 1.15rem; }
    .ap-tabs .nav-link { padding: 8px 11px; font-size: 0.8rem; }
}
</style>
