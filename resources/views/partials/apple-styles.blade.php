<style>
/* ══════════════════════════════════════════════════════
   Participant components, on the shared tokens (partials/ui-tokens).
   Usahawan read this on a phone, many with older eyes: larger type, 48px
   targets, plain status words. Class names are unchanged, so every view
   takes this without an edit.
   Never spell a Blade directive out in here — Blade compiles them inside CSS
   comments too.
   ══════════════════════════════════════════════════════ */
:root {
    /* Old names, kept so inline styles in views still resolve. */
    --ap-text: var(--ui-text);
    --ap-text2: var(--ui-text-2);
    --ap-muted: var(--ui-muted);
    --ap-placeholder: var(--ui-muted);
    --ap-accent: var(--ui-blue);
    --ap-accent-tx: var(--ui-blue-text);
    --ap-accent-hv: var(--ui-blue-hover);
    --ap-green: var(--ui-ok);
    --ap-orange: var(--ui-warn);
    --ap-red: var(--ui-bad);
    --ap-purple: var(--ui-violet-text);
    --ap-teal: var(--ui-blue);
    --ap-purple-alt: var(--ui-violet-text);
    --ap-green-tx: var(--ui-ok-text);
    --ap-orange-tx: var(--ui-warn-text);
    --ap-red-tx: var(--ui-bad-text);
    --ap-purple-tx: var(--ui-violet-text);
    --ap-gray-tx: var(--ui-neutral-text);
    --ap-blue-tx: var(--ui-blue-text);
    --ap-alert-green: var(--ui-ok-text);
    --ap-alert-red: var(--ui-bad-text);
    --ap-alert-orange: var(--ui-warn-text);
    --ap-alert-blue: var(--ui-blue-text);
    --ap-surface: var(--ui-surface);
    --ap-surface-alt: var(--ui-surface-2);
    --ap-border: var(--ui-border);
    --ap-border-soft: var(--ui-sep);
    --ap-sep: var(--ui-border-strong);
    --ap-field-bg: var(--ui-surface);
    --ap-font: var(--ui-font);
}

.ap-page { font-family: var(--ui-font); color: var(--ui-text); }

/* ── Page header ───────────────────────────────────── */
.ap-page-header { display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 12px; margin-bottom: 24px; }
.ap-page-title { margin: 0 0 4px; font-size: 1.75rem; font-weight: 700; line-height: 1.2; letter-spacing: -0.015em; color: var(--ui-text); }
.ap-page-subtitle { margin: 0; font-size: 1rem; color: var(--ui-muted); }
.ap-page-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }

.ap-breadcrumb { display: flex; align-items: center; gap: 6px; margin-bottom: 8px; font-size: 0.875rem; color: var(--ui-muted); }
.ap-breadcrumb a { color: var(--ui-blue-text); text-decoration: underline; }
.ap-breadcrumb .sep { color: var(--ui-border-strong); }

/* ── Cards ─────────────────────────────────────────── */
.ap-card { overflow: hidden; background: var(--ui-surface); border: 1px solid var(--ui-border); border-radius: var(--ui-r-lg); box-shadow: var(--ui-shadow-1); }
.ap-card-padded { padding: 20px 22px; }
.ap-card-body { padding: 20px 22px; }
.ap-card-body-sm { padding: 16px 18px; }
.ap-card-body-lg { padding: 28px; }
.ap-card-header { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 16px 22px; border-bottom: 1px solid var(--ui-sep); }
.ap-card-header-title { display: flex; align-items: center; gap: 10px; margin: 0; font-size: 1.125rem; font-weight: 700; color: var(--ui-text); }
.ap-card-header-title > i { color: var(--ui-muted) !important; }
.ap-card-footer { padding: 14px 22px; background: var(--ui-surface-2); border-top: 1px solid var(--ui-sep); }

/* ── Stat card ─────────────────────────────────────── */
.ap-stat-card { height: 100%; padding: 20px; background: var(--ui-surface); border: 1px solid var(--ui-border); border-radius: var(--ui-r-lg); box-shadow: var(--ui-shadow-1); }
.ap-stat-icon { width: 44px; height: 44px; flex-shrink: 0; display: grid; place-items: center; margin-bottom: 12px; border-radius: var(--ui-r); font-size: 1.125rem; background: var(--ui-blue-tint); color: var(--ui-blue-text); }
.ap-stat-icon.green { background: var(--ui-ok-tint); color: var(--ui-ok-text); }
.ap-stat-icon.orange { background: var(--ui-warn-tint); color: var(--ui-warn-text); }
.ap-stat-icon.red { background: var(--ui-bad-tint); color: var(--ui-bad-text); }
.ap-stat-icon.purple { background: var(--ui-violet-tint); color: var(--ui-violet-text); }
.ap-stat-value { margin-bottom: 4px; font-size: 1.75rem; font-weight: 700; line-height: 1; color: var(--ui-text); font-variant-numeric: tabular-nums; }
.ap-stat-label { font-size: 1rem; font-weight: 500; color: var(--ui-muted); }

/* ── Buttons ───────────────────────────────────────── */
.ap-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    min-height: var(--ui-control-h-lg); padding: 0 20px; border: 1px solid transparent; border-radius: var(--ui-r);
    font-size: 1rem; font-weight: 600; line-height: 1.2; text-decoration: none; white-space: nowrap; cursor: pointer;
    transition: background-color var(--ui-fast) var(--ui-ease), border-color var(--ui-fast) var(--ui-ease), color var(--ui-fast) var(--ui-ease), box-shadow var(--ui-fast) var(--ui-ease);
}
.ap-btn-primary { background: var(--ui-blue); color: #fff; box-shadow: var(--ui-shadow-1), inset 0 -2px 0 rgba(0, 0, 0, 0.15); }
.ap-btn-primary:hover { background: var(--ui-blue-hover); color: #fff; }
.ap-btn-primary:active { background: var(--ui-blue-press); }
.ap-btn-secondary { background: var(--ui-surface); color: var(--ui-text); border-color: var(--ui-border-strong); box-shadow: var(--ui-shadow-1); }
.ap-btn-secondary:hover { background: var(--ui-surface-2); color: var(--ui-text); border-color: var(--ui-border-hover); }
.ap-btn-outline { background: var(--ui-surface); color: var(--ui-blue-text); border-color: var(--ui-blue-border-2); }
.ap-btn-outline:hover { background: var(--ui-blue-tint); color: var(--ui-blue-press); border-color: var(--ui-blue); }
.ap-btn-danger { background: var(--ui-surface); color: var(--ui-bad-text); border-color: var(--ui-bad-border-2); }
.ap-btn-danger:hover { background: var(--ui-bad); color: #fff; border-color: var(--ui-bad); }
.ap-btn-success { background: var(--ui-ok); color: #fff; }
.ap-btn-success:hover { background: var(--ui-ok-text); color: #fff; }
.ap-btn-sm { min-height: 40px; padding: 0 14px; font-size: 0.875rem; }
.ap-btn-lg { min-height: 56px; padding: 0 28px; font-size: 1.125rem; }
.ap-btn:disabled, .ap-btn[disabled] { opacity: 0.45; cursor: not-allowed; pointer-events: none; box-shadow: none; }

/* ── Form inputs ───────────────────────────────────── */
.ap-input, .ap-select, .ap-textarea {
    width: 100%; min-height: var(--ui-control-h-lg); padding: 10px 14px;
    background: var(--ui-surface); border: 1px solid var(--ui-border-strong); border-radius: var(--ui-r);
    color: var(--ui-text); font-size: 1rem; outline: none; appearance: none; -webkit-appearance: none;
    transition: border-color var(--ui-fast) var(--ui-ease), box-shadow var(--ui-fast) var(--ui-ease);
}
.ap-input:hover, .ap-select:hover, .ap-textarea:hover { border-color: var(--ui-border-hover); }
.ap-input:focus, .ap-select:focus, .ap-textarea:focus { border-color: var(--ui-blue); box-shadow: 0 0 0 3px rgba(26, 86, 219, 0.18); }
.ap-input::placeholder, .ap-textarea::placeholder { color: var(--ui-muted); }
.ap-label { display: block; margin-bottom: 6px; font-size: 1rem; font-weight: 600; color: var(--ui-text-2); }
.ap-form-group { margin-bottom: 18px; }
.ap-filter-bar { margin-bottom: 18px; padding: 14px; background: var(--ui-surface); border: 1px solid var(--ui-border); border-radius: var(--ui-r-lg); }

/* The confirm checkbox is the most important control a usahawan touches. */
.ap-page .form-check-input, .ap-card .form-check-input { width: 1.4em; height: 1.4em; margin-top: 0.1em; border: 2px solid var(--ui-border-strong); }
.ap-page .form-check-input:checked, .ap-card .form-check-input:checked { background-color: var(--ui-blue); border-color: var(--ui-blue); }
.ap-page .form-check-label, .ap-card .form-check-label { padding-left: 4px; font-size: 1rem; }

/* ── Table ─────────────────────────────────────────── */
.ap-table { width: 100%; border-collapse: separate; border-spacing: 0; }
.ap-table thead th { padding: 10px 16px; background: var(--ui-surface-2); border-bottom: 1px solid var(--ui-border); font-size: 0.8125rem; font-weight: 600; color: var(--ui-muted); white-space: nowrap; }
.ap-table tbody td { padding: 14px 16px; border-bottom: 1px solid var(--ui-sep); font-size: 1rem; color: var(--ui-text); vertical-align: middle; }
.ap-table tbody tr:last-child td { border-bottom: none; }

/* ── Status badges ─────────────────────────────────── */
.ap-badge {
    display: inline-flex; align-items: center; gap: 6px; min-height: 28px; padding: 3px 12px 3px 10px;
    border-radius: 999px; background: var(--ui-neutral-tint); color: var(--ui-neutral-text);
    font-size: 0.875rem; font-weight: 600; white-space: nowrap;
}
.ap-badge::before { content: ""; width: 7px; height: 7px; flex-shrink: 0; border-radius: 50%; background: currentColor; }
.ap-badge-green { background: var(--ui-ok-tint); color: var(--ui-ok-text); }
.ap-badge-blue { background: var(--ui-blue-tint); color: var(--ui-blue-text); }
.ap-badge-orange { background: var(--ui-warn-tint); color: var(--ui-warn-text); }
.ap-badge-red { background: var(--ui-bad-tint); color: var(--ui-bad-text); }
.ap-badge-purple { background: var(--ui-violet-tint); color: var(--ui-violet-text); }
.ap-badge-gray { background: var(--ui-neutral-tint); color: var(--ui-neutral-text); }

/* ── Alerts ────────────────────────────────────────── */
.ap-alert { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 16px; padding: 14px 16px; border: 1px solid transparent; border-radius: var(--ui-r-lg); font-size: 1rem; line-height: 1.5; }
.ap-alert-icon { flex-shrink: 0; margin-top: 2px; font-size: 1.125rem; }
.ap-alert-success { background: var(--ui-ok-tint); border-color: var(--ui-ok-border); color: var(--ui-ok-text); }
.ap-alert-danger { background: var(--ui-bad-tint); border-color: var(--ui-bad-border); color: var(--ui-bad-text); }
.ap-alert-warning { background: var(--ui-warn-tint); border-color: var(--ui-warn-border); color: var(--ui-warn-text); }
.ap-alert-info { background: var(--ui-blue-tint); border-color: var(--ui-info-border); color: var(--ui-blue-text); }
.ap-page .alert, .ap-card .alert { display: flex; align-items: flex-start; gap: 10px; border: 1px solid transparent; border-radius: var(--ui-r-lg); font-size: 1rem; line-height: 1.5; padding: 14px 44px 14px 16px; }
.ap-page .alert-success, .ap-card .alert-success { background: var(--ui-ok-tint); border-color: var(--ui-ok-border); color: var(--ui-ok-text); }
.ap-page .alert-danger, .ap-card .alert-danger { background: var(--ui-bad-tint); border-color: var(--ui-bad-border); color: var(--ui-bad-text); }

/* ── Section title, divider, empty ─────────────────── */
.ap-section-head { margin: 36px 0 12px; }
.ap-section-title { margin: 6px 0 10px; font-size: 1.125rem; font-weight: 700; color: var(--ui-text); }
.ap-divider { margin: 18px 0; border: none; border-top: 1px solid var(--ui-sep); opacity: 1; }
.ap-empty { padding: 44px 24px; text-align: center; color: var(--ui-muted); }
.ap-empty-icon { display: block; margin-bottom: 12px; font-size: 2.25rem; color: var(--ui-border-strong); }
.ap-empty-title { margin-bottom: 6px; font-size: 1.125rem; font-weight: 700; color: var(--ui-text-2); }
.ap-empty-text { margin-bottom: 16px; font-size: 1rem; color: var(--ui-muted); }

/* ── Progress ──────────────────────────────────────── */
.ap-progress { height: 8px; overflow: hidden; border-radius: 999px; background: var(--ui-neutral-tint); }
.ap-progress-bar { height: 100%; border-radius: 999px; background: var(--ui-blue); }
.ap-progress-bar.green { background: var(--ui-ok); }
.ap-progress-bar.orange { background: var(--ui-warn); }
.ap-progress-bar.red { background: var(--ui-bad); }

/* ── Detail rows and lists ─────────────────────────── */
.ap-detail-row { display: flex; align-items: flex-start; justify-content: space-between; gap: 14px; padding: 12px 0; border-bottom: 1px solid var(--ui-sep); font-size: 1rem; }
.ap-detail-row:last-child { border-bottom: none; }
.ap-detail-label { flex-shrink: 0; min-width: 120px; font-weight: 500; color: var(--ui-muted); }
.ap-detail-value { font-weight: 600; text-align: right; color: var(--ui-text); }
.ap-list { list-style: none; margin: 0; padding: 0; }
.ap-list-item { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 12px 0; border-bottom: 1px solid var(--ui-sep); font-size: 1rem; }
.ap-list-item:last-child { border-bottom: none; }

/* ── Timeline ──────────────────────────────────────── */
.ap-timeline { padding-left: 18px; border-left: 2px solid var(--ui-border); }
.ap-timeline-item { position: relative; padding: 0 0 18px 14px; }
.ap-timeline-item:last-child { padding-bottom: 0; }
.ap-timeline-dot { position: absolute; left: -25px; top: 4px; width: 12px; height: 12px; border-radius: 50%; border: 2px solid var(--ui-surface); background: currentColor; box-shadow: 0 0 0 2px currentColor; }
.ap-timeline-dot.green { color: var(--ui-ok); }
.ap-timeline-dot.blue { color: var(--ui-blue); }
.ap-timeline-dot.orange { color: var(--ui-warn); }
.ap-timeline-dot.red { color: var(--ui-bad); }
.ap-timeline-dot.gray { color: var(--ui-border-strong); }
.ap-timeline-label { margin-bottom: 2px; font-size: 1rem; font-weight: 600; color: var(--ui-text); }
.ap-timeline-desc { font-size: 0.875rem; color: var(--ui-muted); }

/* ── Identity and fact grid ────────────────────────── */
.ap-identity-logo { width: 88px; height: 88px; flex-shrink: 0; display: grid; place-items: center; overflow: hidden; border: 1px solid var(--ui-border); border-radius: var(--ui-r-lg); background: var(--ui-surface-2); }
.ap-identity-logo img { width: 100%; height: 100%; object-fit: cover; }
.ap-identity-logo i { font-size: 2.25rem; color: var(--ui-border-strong); }
.ap-identity-name { margin: 0 0 8px; font-size: 1.5rem; font-weight: 700; color: var(--ui-text); }
.ap-factgrid { display: grid; grid-template-columns: repeat(auto-fit, minmax(13.5rem, 1fr)); gap: 12px 20px; padding-top: 18px; border-top: 1px solid var(--ui-sep); }
.ap-factgrid dt { margin-bottom: 2px; font-size: 0.8125rem; font-weight: 600; color: var(--ui-muted); }
.ap-factgrid dd { margin: 0; font-size: 1rem; color: var(--ui-text); }

/* ── Tabs ──────────────────────────────────────────── */
.ap-tabs { display: flex; flex-wrap: wrap; gap: 4px; margin: 0; padding: 0 8px; list-style: none; border-bottom: 1px solid var(--ui-border); }
.ap-tabs .nav-link { display: inline-flex; align-items: center; gap: 8px; min-height: 44px; margin-bottom: -1px; padding: 0 14px; border: 0; border-bottom: 2px solid transparent; border-radius: 0; background: none; color: var(--ui-muted); font-weight: 600; }
.ap-tabs .nav-link:hover { color: var(--ui-text); }
.ap-tabs .nav-link.active { color: var(--ui-text); border-bottom-color: var(--ui-blue); background: none; }
.ap-tab-dot { width: 7px; height: 7px; border-radius: 50%; background: var(--ui-border-strong); }

.ap-muted { font-size: 1rem; color: var(--ui-muted); }

/* ── A draw, as a usahawan sees it ─────────────────── */
/* One card per draw on the dashboard: the fair, where its draw is, and the
   single thing left to do. The stepper reads larger here than in the office. */
.ap-draw { padding: 22px; }
.ap-draw-head { display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 12px 20px; }
.ap-draw-title { margin: 0 0 4px; font-size: 1.25rem; font-weight: 700; color: var(--ui-text); }
.ap-draw-meta { display: flex; flex-wrap: wrap; gap: 4px 16px; font-size: 1rem; color: var(--ui-muted); }
.ap-draw-meta i { margin-right: 4px; }
.ap-draw .ui-stepper { margin: 22px 0 18px; }
.ap-draw .ui-stepper .label { font-size: 0.875rem; }
.ap-draw-next { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; padding-top: 16px; border-top: 1px solid var(--ui-sep); font-size: 1rem; color: var(--ui-text-2); }
.ap-draw-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 440px), 1fr)); gap: 16px; align-items: start; }
.ap-draw-grid > .ap-draw { margin: 0 !important; }
/* A request to join: what was sent, read back. */
.ap-request-body { margin-top: 16px; font-size: 1rem; line-height: 1.6; color: var(--ui-text-2); }
.ap-request-body ul { margin: 8px 0 0; padding: 0; list-style: none; }
.ap-request-body i { margin-right: 4px; }
.ap-request-body + .ap-draw-next { margin-top: 16px; }

/* ── The dashboard ─────────────────────────────────── */
/* What to do now on top, the draws and requests beside what is open and what
   is on record. Rows mirror the office's Needs attention: a state tint, its
   hairline, the words in its -text, and the one next step at the end. */
.ap-dash .ap-section-title { margin: 0 0 12px; }
.ap-todo { margin-bottom: 8px; }
.ap-todo-row { display: flex; flex-wrap: wrap; align-items: center; gap: 8px 12px; min-height: 56px; margin: 8px 0 0; padding: 12px 16px; border: 1px solid transparent; border-radius: var(--ui-r); font-size: 1rem; line-height: 1.45; text-decoration: none; transition: border-color 140ms var(--ui-ease); }
.ap-todo-row > .bi { flex-shrink: 0; font-size: 1.125rem; }
.ap-todo-text { flex: 1 1 0; min-width: 0; }
.ap-todo-go { margin-left: auto; font-weight: 600; white-space: nowrap; }
.ap-todo-go .bi { font-size: 0.875rem; }
.ap-todo-bad { background: var(--ui-bad-tint); border-color: var(--ui-bad-border); color: var(--ui-bad-text); }
.ap-todo-warn { background: var(--ui-warn-tint); border-color: var(--ui-warn-border); color: var(--ui-warn-text); }
.ap-todo-info { background: var(--ui-blue-tint); border-color: var(--ui-info-border); color: var(--ui-blue-text); }
.ap-todo-ok { background: var(--ui-ok-tint); border-color: var(--ui-ok-border); color: var(--ui-ok-text); }
a.ap-todo-row:hover { border-color: currentColor; color: inherit; }
a.ap-todo-bad:hover { color: var(--ui-bad-text); }
a.ap-todo-warn:hover { color: var(--ui-warn-text); }
a.ap-todo-info:hover { color: var(--ui-blue-text); }
.ap-dash-grid { display: grid; grid-template-columns: minmax(0, 3fr) minmax(0, 2fr); gap: 24px; align-items: start; margin-top: 28px; }
.ap-dash-main { display: grid; gap: 36px; min-width: 0; }
.ap-dash-main .ap-draw-grid { grid-template-columns: minmax(0, 1fr); }
.ap-dash-side { display: grid; gap: 16px; min-width: 0; }
.ap-open-row { display: grid; gap: 6px; justify-items: start; padding: 14px 0; border-top: 1px solid var(--ui-sep); }
.ap-open-row:first-of-type { padding-top: 0; border-top: 0; }
.ap-open-row:last-child { padding-bottom: 0; }
.ap-open-title { font-size: 1rem; font-weight: 650; color: var(--ui-text); }
.ap-open-meta { display: flex; flex-wrap: wrap; gap: 2px 14px; font-size: 0.875rem; color: var(--ui-muted); font-variant-numeric: tabular-nums; }
.ap-open-meta .bi { margin-right: 4px; }
.ap-open-row .ap-btn { margin-top: 4px; }
.ap-facts { margin: 0 0 14px; }
.ap-facts dt { font-size: 0.875rem; font-weight: 600; color: var(--ui-muted); }
.ap-facts dd { margin: 2px 0 12px; font-size: 1rem; color: var(--ui-text); }
.ap-facts dd:last-child { margin-bottom: 0; }
.ap-tabular { font-variant-numeric: tabular-nums; }
.ap-side-note { font-size: 0.875rem; line-height: 1.5; color: var(--ui-muted); }
/* Past events: a table on a desk; on a phone each row is a block of
   label-and-value lines, the label taken from data-label. */
.ap-table-card { padding: 0; }
.ap-table-card .ap-table td:first-child { font-weight: 600; }
/* Short values stay on one line; words wrap. */
.ap-past-table td[data-label="Booth"], .ap-past-table td[data-label="Lot"], .ap-past-table td[data-label="Photos"] { white-space: nowrap; }
.ap-past-table td:first-child { min-width: 220px; }
.ap-table td.ap-tabular, .ap-table .ap-tabular { font-variant-numeric: tabular-nums; }
.ap-past-table td { vertical-align: top; }
.ap-past-meta { display: block; margin-top: 2px; font-size: 0.875rem; font-weight: 400; color: var(--ui-muted); }
.ap-lot-code { font-weight: 700; color: var(--ui-gold-text); font-variant-numeric: tabular-nums; }
.ap-table .ap-muted { color: var(--ui-muted); }
@media (max-width: 640px) {
    .ap-table-stack thead { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); }
    .ap-table-stack, .ap-table-stack tbody, .ap-table-stack tr, .ap-table-stack td { display: block; }
    .ap-table-stack tr { padding: 14px 18px; border-bottom: 1px solid var(--ui-sep); }
    .ap-table-stack tr:last-child { border-bottom: 0; }
    .ap-table-stack tbody td { display: flex; justify-content: space-between; gap: 16px; padding: 4px 0; border: 0; white-space: normal; text-align: right; }
    .ap-table-stack tbody td::before { content: attr(data-label); flex-shrink: 0; font-size: 0.875rem; font-weight: 600; color: var(--ui-muted); text-align: left; }
    .ap-table-stack tbody td:first-child { display: block; min-width: 0; padding-bottom: 6px; font-size: 1.125rem; text-align: left; }
    .ap-table-stack tbody td > * { min-width: 0; overflow-wrap: anywhere; }
    .ap-table-stack tbody td:first-child::before { content: none; }
}

/* The profile: facts and the password form beside the record, half and half. */
.ap-profile-grid { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); margin-top: 0; }
.ap-field { margin-bottom: 16px; }
.ap-field-hint { margin: 6px 0 0; font-size: 0.875rem; color: var(--ui-muted); }
.ap-field-error { margin: 6px 0 0; font-size: 0.875rem; font-weight: 600; color: var(--ui-bad-text); }
.ap-input[aria-invalid="true"] { border-color: var(--ui-bad); }
@media (max-width: 860px) {
    .ap-dash-grid { grid-template-columns: minmax(0, 1fr); }
}
/* On a phone the next step takes its own line under the sentence, lined up
   with it, so the sentence keeps the width. */
@media (max-width: 576px) {
    .ap-todo-row { align-items: flex-start; }
    .ap-todo-row > .bi { margin-top: 2px; }
    .ap-todo-go { flex-basis: 100%; margin-left: 30px; }
}
.ap-draw-lot-link { gap: 8px; border-color: var(--ui-gold) !important; background: var(--ui-gold-tint) !important; color: var(--ui-gold-text) !important; font-size: 1rem; }
.ap-draw-lot-link:hover { background: var(--ui-gold-tint-2) !important; }
.ap-draw-lot-link i { color: var(--ui-gold); }

@media (max-width: 576px) {
    .ap-page-header { flex-direction: column; }
    .ap-page-title { font-size: 1.5rem; }
    .ap-detail-row { flex-direction: column; gap: 2px; }
    .ap-detail-value { text-align: left; }
    .ap-draw { padding: 18px; }
    .ap-draw-next .ap-btn { width: 100%; }
}

@media (prefers-reduced-motion: reduce) {
    .ap-btn, .ap-input, .ap-select, .ap-textarea { transition: none; }
}
</style>
