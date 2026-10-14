<style>
    /* ── Admin components, on the shared tokens (partials/ui-tokens) ──────────
       The office works here for hours: Stripe-calm tables and forms, one blue
       that acts, status that names itself. Class names are unchanged, so every
       admin view takes this without an edit. */
    :root {
        /* The old names, kept so inline styles in views still resolve. */
        --mac-primary: var(--ui-blue);
        --mac-primary-hv: var(--ui-blue-hover);
        --mac-primary-tx: var(--ui-blue-text);
        --mac-primary-lt: var(--ui-blue-tint);
        --mac-primary-md: #d7e3fb;
        --mac-primary-rg: rgba(26, 86, 219, 0.18);
        --mac-text: var(--ui-text);
        --mac-text2: var(--ui-text-2);
        --mac-muted: var(--ui-muted);
        --mac-bg: var(--ui-ground);
        --mac-border: var(--ui-border);
        --mac-sep: var(--ui-sep);
    }

    .mac-page { font-family: var(--ui-font); color: var(--ui-text); }

    /* ── Page header ─────────────────────────────── */
    .mac-header h2 { font-size: 1.5rem; font-weight: 700; letter-spacing: -0.015em; color: var(--ui-text); margin: 0 0 4px; line-height: 1.25; }
    .mac-header p { color: var(--ui-muted); }
    .mac-breadcrumb { font-size: 0.82rem; color: var(--ui-muted); }
    .mac-breadcrumb a { color: var(--ui-blue-text); text-decoration: underline; }
    .mac-breadcrumb .sep { margin: 0 6px; color: var(--ui-border-strong); }

    /* ── Buttons ─────────────────────────────────── */
    .mac-btn-primary, .mac-btn-secondary, .mac-btn-danger, .mac-btn-success, .mac-btn-warning {
        display: inline-flex; align-items: center; justify-content: center; gap: 7px;
        min-height: var(--ui-control-h); padding: 0 16px; border-radius: var(--ui-r);
        font-size: 0.875rem; font-weight: 600; line-height: 1; text-decoration: none; white-space: nowrap;
        border: 1px solid transparent; cursor: pointer;
        transition: background-color var(--ui-fast) var(--ui-ease), border-color var(--ui-fast) var(--ui-ease), color var(--ui-fast) var(--ui-ease), box-shadow var(--ui-fast) var(--ui-ease);
    }
    .mac-btn-primary { background: var(--ui-blue); color: #fff; box-shadow: var(--ui-shadow-1), inset 0 -1px 0 rgba(0, 0, 0, 0.12); }
    .mac-btn-primary:hover { background: var(--ui-blue-hover); color: #fff; }
    .mac-btn-primary:active { background: var(--ui-blue-press); }
    .mac-btn-secondary { background: var(--ui-surface); color: var(--ui-text); border-color: var(--ui-border-strong); box-shadow: var(--ui-shadow-1); }
    .mac-btn-secondary:hover { background: var(--ui-surface-2); color: var(--ui-text); border-color: #a9b4c3; }
    .mac-btn-danger { background: var(--ui-surface); color: var(--ui-bad-text); border-color: #f1b8a8; box-shadow: var(--ui-shadow-1); }
    .mac-btn-danger:hover { background: var(--ui-bad); color: #fff; border-color: var(--ui-bad); }
    .mac-btn-success { background: var(--ui-ok); color: #fff; box-shadow: var(--ui-shadow-1), inset 0 -1px 0 rgba(0, 0, 0, 0.14); }
    .mac-btn-success:hover { background: var(--ui-ok-text); color: #fff; }
    .mac-btn-warning { background: var(--ui-surface); color: var(--ui-warn-text); border-color: #efc98a; box-shadow: var(--ui-shadow-1); }
    .mac-btn-warning:hover { background: var(--ui-warn-tint); color: var(--ui-warn-text); }

    /* ── Filter bar ──────────────────────────────── */
    .mac-filter-bar {
        background: var(--ui-surface); border: 1px solid var(--ui-border); border-radius: var(--ui-r-lg);
        padding: 12px; margin-bottom: 16px; box-shadow: var(--ui-shadow-1);
    }
    /* A filter form lays its fields out in one row that wraps, whatever markup
       a view used: search widest, selects next, the buttons at their own size. */
    form.mac-filter-bar { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
    form.mac-filter-bar > .row { flex: 1 1 100%; }
    form.mac-filter-bar > input { flex: 2 1 240px; width: auto; }
    form.mac-filter-bar > select { flex: 1 1 170px; width: auto; }
    form.mac-filter-bar > button, form.mac-filter-bar > a { flex: 0 0 auto; }
    .mac-input, .mac-select, .mac-form-control {
        width: 100%; height: var(--ui-control-h); box-sizing: border-box;
        padding: 0 12px; border-radius: var(--ui-r);
        background: var(--ui-surface); border: 1px solid var(--ui-border-strong);
        color: var(--ui-text); font-size: 0.9rem; line-height: 1.4; outline: none;
        transition: border-color var(--ui-fast) var(--ui-ease), box-shadow var(--ui-fast) var(--ui-ease);
    }
    .mac-select {
        appearance: none; -webkit-appearance: none; padding-right: 34px;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 16 16'%3E%3Cpath fill='none' stroke='%23586477' stroke-width='1.6' stroke-linecap='round' stroke-linejoin='round' d='M4 6l4 4 4-4'/%3E%3C/svg%3E");
        background-repeat: no-repeat; background-position: right 10px center;
    }
    select.mac-select[multiple], select.mac-form-control[multiple] { height: auto; padding: 6px 8px; background-image: none; }
    .mac-input:hover, .mac-select:hover, .mac-form-control:hover { border-color: #a9b4c3; }
    .mac-input:focus, .mac-select:focus, .mac-form-control:focus { border-color: var(--ui-blue); box-shadow: 0 0 0 3px var(--mac-primary-rg); }
    .mac-input:focus-visible, .mac-select:focus-visible, .mac-form-control:focus-visible { box-shadow: var(--ui-focus-ring) !important; border-color: var(--ui-focus-ink); }
    .mac-input::placeholder, .mac-form-control::placeholder { color: var(--ui-muted); }
    textarea.mac-form-control { height: auto; min-height: 96px; padding: 10px 12px; resize: vertical; line-height: 1.5; }
    .mac-filter-btn, .mac-clear-btn {
        display: inline-flex; align-items: center; justify-content: center; gap: 6px;
        min-height: var(--ui-control-h); padding: 0 16px; border-radius: var(--ui-r);
        font-size: 0.875rem; font-weight: 600; text-decoration: none; cursor: pointer;
        transition: background-color var(--ui-fast) var(--ui-ease), border-color var(--ui-fast) var(--ui-ease);
    }
    .mac-filter-btn { background: var(--ui-blue); color: #fff; border: 1px solid transparent; }
    .mac-filter-btn:hover { background: var(--ui-blue-hover); }
    .mac-clear-btn { background: transparent; color: var(--ui-text-2); border: 1px solid transparent; }
    .mac-clear-btn:hover { background: var(--ui-neutral-tint); color: var(--ui-text); }

    /* ── Card ────────────────────────────────────── */
    .mac-card { background: var(--ui-surface); border: 1px solid var(--ui-border); border-radius: var(--ui-r-lg); box-shadow: var(--ui-shadow-1); overflow: hidden; }
    .mac-card-padded { padding: 24px; }
    .mac-card-header { padding: 14px 20px; border-bottom: 1px solid var(--ui-sep); background: var(--ui-surface); }
    .mac-card-title { font-size: 1rem; font-weight: 650; color: var(--ui-text); letter-spacing: -0.005em; display: flex; align-items: center; gap: 8px; margin: 0; }
    .mac-card-title > i { color: var(--ui-muted); }
    .mac-card > .mac-card-title, .mac-card-padded > .mac-card-title { margin-bottom: 12px; }

    /* ── Table ───────────────────────────────────── */
    .mac-table { width: 100%; border-collapse: separate; border-spacing: 0; }
    .mac-table thead th {
        background: var(--ui-surface-2); padding: 10px 16px;
        font-size: 0.75rem; font-weight: 600; letter-spacing: 0.02em; color: var(--ui-muted);
        border-bottom: 1px solid var(--ui-border); white-space: nowrap; text-align: left;
    }
    .mac-table thead th.text-center { text-align: center; }
    .mac-table thead th.text-end { text-align: right; }
    .mac-table tbody td { padding: 13px 16px; font-size: 0.875rem; color: var(--ui-text); border-bottom: 1px solid var(--ui-sep); vertical-align: middle; }
    .mac-table tbody tr:last-child td { border-bottom: none; }
    .mac-table tbody tr { transition: background-color var(--ui-fast) var(--ui-ease); }
    .mac-table tbody tr:hover td { background: #f6f9ff; }
    .mac-table tbody td a.fw-semibold, .mac-table tbody td a.text-decoration-none { color: var(--ui-text); }
    .mac-table tbody td a.fw-semibold:hover { color: var(--ui-blue-text); }

    /* ── Status pills ─────────────────────────────── */
    .mac-status {
        display: inline-flex; align-items: center; gap: 6px; padding: 2px 10px 2px 8px; min-height: 24px;
        border-radius: 999px; font-size: 0.78rem; font-weight: 600; line-height: 1.2; white-space: nowrap;
        background: var(--ui-neutral-tint); color: var(--ui-neutral-text);
    }
    .mac-status-dot { width: 7px; height: 7px; border-radius: 50%; display: inline-block; background: currentColor; }
    .mac-status-green  { background: var(--ui-ok-tint);   color: var(--ui-ok-text); }
    .mac-status-gray   { background: var(--ui-neutral-tint); color: var(--ui-neutral-text); }
    .mac-status-orange, .mac-status-yellow { background: var(--ui-warn-tint); color: var(--ui-warn-text); }
    .mac-status-red    { background: var(--ui-bad-tint);  color: var(--ui-bad-text); }
    .mac-status-blue   { background: var(--ui-blue-tint); color: var(--ui-blue-text); }
    .mac-status-purple { background: var(--ui-violet-tint); color: var(--ui-violet-text); }

    /* ── Row actions ─────────────────────────────── */
    .mac-action-btn, .mac-action-btn-danger {
        display: inline-flex; align-items: center; justify-content: center; gap: 5px;
        min-height: 32px; padding: 0 12px; border-radius: var(--ui-r-sm);
        font-size: 0.82rem; font-weight: 600; text-decoration: none; cursor: pointer;
        background: var(--ui-surface); border: 1px solid var(--ui-border-strong);
        transition: background-color var(--ui-fast) var(--ui-ease), border-color var(--ui-fast) var(--ui-ease), color var(--ui-fast) var(--ui-ease);
    }
    .mac-action-btn { color: var(--ui-text-2); }
    .mac-action-btn:hover { background: var(--ui-blue-tint); border-color: #b6c9f4; color: var(--ui-blue-text); }
    .mac-action-btn-danger { color: var(--ui-bad-text); border-color: #f1b8a8; }
    .mac-action-btn-danger:hover { background: var(--ui-bad); border-color: var(--ui-bad); color: #fff; }
    .mac-action-btn-field { height: var(--ui-control-h); min-height: var(--ui-control-h); padding: 0 12px; border-radius: var(--ui-r); }

    .mac-btn-primary:disabled, .mac-btn-secondary:disabled, .mac-btn-danger:disabled,
    .mac-btn-success:disabled, .mac-btn-warning:disabled, .mac-action-btn:disabled, .mac-action-btn-danger:disabled,
    .mac-filter-btn:disabled {
        opacity: 0.45; cursor: not-allowed; pointer-events: none; box-shadow: none;
    }

    /* ── Form elements ───────────────────────────── */
    .mac-label { display: block; margin-bottom: 6px; font-size: 0.85rem; font-weight: 600; color: var(--ui-text-2); }
    .mac-page .form-check-input { width: 1.15em; height: 1.15em; border-color: var(--ui-border-strong); }
    .mac-page .form-check-input:checked { background-color: var(--ui-blue); border-color: var(--ui-blue); }
    .mac-page .form-check-input:focus { border-color: var(--ui-blue); box-shadow: 0 0 0 3px var(--mac-primary-rg); }
    .mac-page .form-check-label { color: var(--ui-text-2); }
    .mac-page .text-danger { color: var(--ui-bad-text) !important; }
    .mac-page small.text-danger { display: inline-block; margin-top: 4px; font-size: 0.82rem; }

    .input-group { flex-wrap: nowrap; }
    .input-group > .mac-form-control { flex: 1 1 auto; width: 1%; min-width: 0; }
    .input-group > .mac-form-control:not(:last-child) { border-top-right-radius: 0; border-bottom-right-radius: 0; border-right: 0; }
    .input-group > .mac-form-control:not(:first-child) { border-top-left-radius: 0; border-bottom-left-radius: 0; }
    .input-group > .input-group-text {
        height: var(--ui-control-h); padding: 0 12px; font-size: 0.875rem;
        background: var(--ui-surface-2); border: 1px solid var(--ui-border-strong); border-radius: var(--ui-r);
        color: var(--ui-muted); white-space: nowrap;
    }
    .input-group > .input-group-text:not(:first-child) { border-top-left-radius: 0; border-bottom-left-radius: 0; }
    .input-group > .input-group-text:not(:last-child) { border-top-right-radius: 0; border-bottom-right-radius: 0; border-right: 0; }

    /* ── Detail rows ─────────────────────────────── */
    .mac-detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0; }
    .mac-detail-row { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; padding: 12px 20px; border-bottom: 1px solid var(--ui-sep); }
    .mac-detail-row:last-child { border-bottom: none; }
    .mac-detail-label { flex-shrink: 0; min-width: 140px; font-size: 0.85rem; font-weight: 500; color: var(--ui-muted); }
    .mac-detail-value { font-size: 0.875rem; font-weight: 500; color: var(--ui-text); text-align: right; }

    .mac-section-title { padding: 18px 20px 8px; border-top: 1px solid var(--ui-sep); font-size: 0.8rem; font-weight: 650; color: var(--ui-text-2); }
    .mac-section-title:first-child { border-top: none; }

    /* ── Empty state ─────────────────────────────── */
    .mac-empty { padding: 44px 24px; text-align: center; color: var(--ui-muted); font-size: 0.9rem; }
    .mac-empty-icon { margin-bottom: 10px; font-size: 2rem; color: var(--ui-border-strong); }
    .mac-empty-text { font-size: 0.92rem; font-weight: 500; color: var(--ui-text-2); }
    .mac-empty a { font-weight: 600; }

    /* ── Pagination ──────────────────────────────── */
    .mac-card .pagination, .mac-page .pagination { gap: 4px; padding: 12px 16px; margin: 0; justify-content: center; }
    .mac-card .page-link, .mac-page .page-link {
        min-width: 34px; height: 34px; display: grid; place-items: center; padding: 0 10px;
        border: 1px solid transparent; border-radius: var(--ui-r-sm); color: var(--ui-text-2); font-size: 0.85rem; font-weight: 600;
    }
    .mac-card .page-link:hover, .mac-page .page-link:hover { background: var(--ui-neutral-tint); color: var(--ui-text); }
    .mac-card .page-item.active .page-link, .mac-page .page-item.active .page-link { background: var(--ui-navy-900); color: #fff; }
    .mac-card .page-item.disabled .page-link, .mac-page .page-item.disabled .page-link { color: var(--ui-border-strong); background: transparent; }

    /* ── Alerts ──────────────────────────────────── */
    .mac-page .alert {
        display: flex; align-items: flex-start; gap: 10px;
        padding: 12px 44px 12px 14px; border-radius: var(--ui-r-lg); font-size: 0.9rem; line-height: 1.5;
        border: 1px solid transparent;
    }
    .mac-page .alert-success { background: var(--ui-ok-tint); border-color: #b9e1ca; color: var(--ui-ok-text); }
    .mac-page .alert-danger { background: var(--ui-bad-tint); border-color: #f5c3b6; color: var(--ui-bad-text); }
    .mac-page .alert-warning { background: var(--ui-warn-tint); border-color: #f3d29a; color: var(--ui-warn-text); }
    .mac-page .alert-info { background: var(--ui-blue-tint); border-color: #c4d5f8; color: var(--ui-blue-text); }
    .mac-page .alert-dismissible .btn-close { padding: 14px 15px; }
    .mac-page .alert ul { margin: 4px 0 0; padding-left: 18px; }

    /* ── Tabs ────────────────────────────────────── */
    .mac-tabs { display: flex; gap: 4px; list-style: none; margin: 0; padding: 0 4px; border-bottom: 1px solid var(--ui-border); }
    .mac-tabs .nav-link {
        position: relative; padding: 10px 14px; margin-bottom: -1px;
        font-size: 0.9rem; font-weight: 600; color: var(--ui-muted);
        background: none; border: none; border-bottom: 2px solid transparent; border-radius: 0; cursor: pointer;
        transition: color var(--ui-fast) var(--ui-ease), border-color var(--ui-fast) var(--ui-ease);
    }
    .mac-tabs .nav-link:hover { color: var(--ui-text); border-bottom-color: var(--ui-border-strong); }
    .mac-tabs .nav-link.active { color: var(--ui-text); border-bottom-color: var(--ui-blue); background: none; }
    .mac-page .nav-tabs { border-bottom: 1px solid var(--ui-border); gap: 4px; }
    .mac-page .nav-tabs .nav-link {
        margin-bottom: -1px; padding: 10px 14px; border: none; border-bottom: 2px solid transparent; border-radius: 0;
        font-size: 0.9rem; font-weight: 600; color: var(--ui-muted); background: none;
    }
    .mac-page .nav-tabs .nav-link:hover { color: var(--ui-text); border-bottom-color: var(--ui-border-strong); }
    .mac-page .nav-tabs .nav-link.active { color: var(--ui-text); border-bottom-color: var(--ui-blue); background: none; }

    /* ── Badges ──────────────────────────────────── */
    .mac-badge {
        display: inline-flex; align-items: center; gap: 5px; padding: 2px 8px; min-height: 22px;
        border-radius: var(--ui-r-sm); font-size: 0.75rem; font-weight: 600; line-height: 1.2; white-space: nowrap;
        background: var(--ui-neutral-tint); color: var(--ui-neutral-text);
    }
    .mac-badge-green  { background: var(--ui-ok-tint);   color: var(--ui-ok-text); }
    .mac-badge-orange { background: var(--ui-warn-tint); color: var(--ui-warn-text); }
    .mac-badge-red    { background: var(--ui-bad-tint);  color: var(--ui-bad-text); }
    .mac-badge-blue   { background: var(--ui-blue-tint); color: var(--ui-blue-text); }
    .mac-badge-gray   { background: var(--ui-neutral-tint); color: var(--ui-neutral-text); }

    /* ── Misc ────────────────────────────────────── */
    .mac-muted { color: var(--ui-muted); font-size: 0.85rem; }
    .mac-text-sm { font-size: 0.8rem; }
    .mac-divider { border: none; border-top: 1px solid var(--ui-sep); margin: 4px 0; opacity: 1; }
    .mac-page .text-warning { color: var(--ui-warn-text) !important; }
    .mac-page .text-success { color: var(--ui-ok-text) !important; }
    .mac-page code { color: var(--ui-violet-text); font-family: var(--ui-font-mono); font-size: 0.85em; }
    .mac-page .fs-3.fw-bold { font-variant-numeric: tabular-nums; letter-spacing: -0.02em; color: var(--ui-text); }
    .mac-page .form-switch .form-check-input { width: 2.2em; }

    .mac-addr-fields { display: grid; grid-template-columns: repeat(auto-fit, minmax(11rem, 1fr)); gap: 8px 18px; margin: 0; }
    .mac-addr-fields dt { margin-bottom: 1px; font-size: 0.78rem; font-weight: 600; color: var(--ui-muted); }
    .mac-addr-fields dd { margin: 0; font-size: 0.875rem; color: var(--ui-text); overflow-wrap: anywhere; }

    /* ── Draw band: the stage and the counts of one draw ── */
    .draw-band { display: flex; flex-wrap: wrap; align-items: center; gap: 20px 40px; padding: 22px 24px; }
    .draw-band-stage { flex: 1 1 420px; min-width: 0; }
    .draw-band-facts { flex: 0 1 auto; display: flex; flex-wrap: wrap; gap: 8px 32px; margin: 0; padding-left: 32px; border-left: 1px solid var(--ui-sep); }
    .draw-band-facts dt { font-size: 0.8rem; font-weight: 500; color: var(--ui-muted); }
    .mac-cell { display: contents; }
    .draw-band-facts dd { font-variant-numeric: tabular-nums; margin: 0; font-size: 1.35rem; font-weight: 700; color: var(--ui-text); letter-spacing: -0.01em; }
    @media (max-width: 767.98px) {
        .draw-band { padding: 18px; }
        .draw-band-facts { padding-left: 0; border-left: 0; width: 100%; justify-content: space-between; }
    }

    /* ── Mobile: tables become cards ─────────────── */
    /* A wide table inside a phone-width scroller puts its row actions where a
       tap lands on the scroller instead. Below 768px each row is a card and
       each cell a label/value line; the labels come from the header (the
       script below), so no view has to annotate its cells. */
    @media (max-width: 767.98px) {
        .mac-table thead { display: none; }
        .mac-table, .mac-table tbody { display: block; width: 100%; }
        .mac-table tbody tr { display: block; margin: 0 0 10px; background: var(--ui-surface); border: 1px solid var(--ui-border); border-radius: var(--ui-r-lg); }
        /* Inside a card the rows are the cards: the outer one gives up its
           chrome rather than nesting card in card. */
        .mac-card:has(.mac-table) { background: none !important; border: 0 !important; box-shadow: none !important; overflow: visible; }
        .mac-card:has(.mac-table) > .mac-card-header { padding: 4px 0 10px; background: none; border: 0; }
        .mac-card .mac-table tbody tr { margin: 0 0 10px; }
        .mac-table tbody td { display: flex; align-items: flex-start; gap: 10px; width: auto; padding: 10px 14px; border-bottom: 1px solid var(--ui-sep); text-align: left !important; }
        .mac-cell { display: block; flex: 1; min-width: 0; }
        .mac-table tbody tr > td:last-child { border-bottom: 0; }
        .mac-table tbody td[data-label]::before { content: attr(data-label); flex: 0 0 38%; font-size: 0.78rem; font-weight: 600; color: var(--ui-muted); }
        .mac-table tbody td[colspan] { display: block; }
        .table-responsive:has(.mac-table) { overflow-x: visible; }
        .table-responsive .d-flex.justify-content-end { flex-wrap: wrap; justify-content: flex-start !important; }
        /* Header rows and their button groups wrap rather than widening the
           document: a too-wide toolbar zooms the whole page out on a phone. */
        .mac-page .d-flex.justify-content-between { flex-wrap: wrap; row-gap: 8px; }
        .mac-page .d-flex.justify-content-between > .d-flex { flex-wrap: wrap; }
        .mac-header h2 { font-size: 1.3rem; }
    }

    @media (prefers-reduced-motion: reduce) {
        .mac-table tbody tr, .mac-btn-primary, .mac-btn-secondary, .mac-input, .mac-select, .mac-form-control { transition: none; }
    }
</style>

{{-- ── Stacked-table labels, derived rather than written ───────────────────────
     The rule above turns every .mac-table into cards below 768px, and a stacked
     cell loses its column header: a bare "3x3" reads as nothing. The CSS takes
     the label from `data-label` on the <td>.

     WHY THIS IS DERIVED AND NOT TYPED. The comment on that rule called the
     attribute opt-in, "add it where a cell is meaningless without it". Measured
     before taking that path: 55 .mac-table instances across 26 views, which is
     roughly 330 <td> to annotate, and every table written after today starts
     wrong again. The header text is already on the page and already correct, so
     copying it is not information anyone needs to supply.

     Opt-in survives where it is worth having: an existing `data-label` is never
     overwritten, so a view that wants a shorter or clearer label than its header
     still wins.

     WHAT IT DELIBERATELY SKIPS
       - a cell with `colspan` — the CSS treats those as full-width bands (the
         rejection-history strip, the per-row summary), not label/value pairs;
       - a cell holding a FORM CONTROL. Measured, after this rule shipped without
         the exclusion and cost six mobile failures: the label takes 38% of a
         343px cell, which leaves 213px, and the code cell on the registrations
         screen holds a `select` with `min-width:160px` beside a submit button -
         339px that cannot shrink. The form overflowed to a scrollWidth of 469
         and the button landed 99px past its own cell's right edge, where
         Playwright reported a neighbouring `<td>` intercepting the click. That
         is entry 56's original defect rebuilt out of its own fix. A control cell
         is not a label/value pair anyway: the button says "Save", the select
         carries an aria-label;
       - a blank header — the actions and checkbox columns have none, and an
         empty ::before would just indent the row;
       - nested tables — `:scope >` keeps an outer table's headers off an inner
         table's cells.

     ITS CEILING, so it is not mistaken for complete: this runs once, on
     DOMContentLoaded. Rows a page builds in JavaScript afterwards get no label.
     None of the tables that stack today are built that way; if one becomes so,
     that page labels its own rows rather than this growing an observer. --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('table.mac-table').forEach(function (table) {
            // The LAST header row, not the first: a grouped header puts the real
            // column names on the lower row.
            var headRow = table.querySelector(':scope > thead > tr:last-of-type');
            if (! headRow) return;

            // Expanded by colspan so a spanning <th> labels each column it covers
            // and everything after it stays aligned.
            var labels = [];
            headRow.querySelectorAll(':scope > th').forEach(function (th) {
                var text = (th.textContent || '').trim();
                for (var i = 0; i < (th.colSpan || 1); i++) labels.push(text);
            });
            if (! labels.length) return;

            table.querySelectorAll(':scope > tbody > tr').forEach(function (row) {
                var column = 0;
                row.querySelectorAll(':scope > td').forEach(function (td) {
                    var label = labels[column];
                    column += td.colSpan || 1;

                    if (td.hasAttribute('data-label') || td.hasAttribute('colspan') || ! label) return;

                    // Controls set their own minimum width and cannot be squeezed into the
                    // 62% a label leaves. Links are not included: they are inline text and
                    // wrap like any other value.
                    if (td.querySelector('form, button, select, input, textarea')) return;

                    td.setAttribute('data-label', label);

                    // One box for the value, so a title and its second line stack
                    // under each other on a phone instead of sitting side by side.
                    // It is `display: contents` above 768px, where nothing changes.
                    var cell = document.createElement('div');
                    cell.className = 'mac-cell';
                    while (td.firstChild) cell.appendChild(td.firstChild);
                    td.appendChild(cell);
                });
            });
        });
    });
</script>
