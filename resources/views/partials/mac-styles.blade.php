<style>
    /* ── Admin Design System — White/Amber theme ── */
    :root {
        --mac-primary:    #D97706;
        --mac-primary-hv: #B45309;
        --mac-primary-tx: #92400E;
        --mac-primary-lt: rgba(217, 119, 6, 0.10);
        --mac-primary-md: rgba(217, 119, 6, 0.18);
        --mac-primary-rg: rgba(217, 119, 6, 0.15);
        --mac-text:       #1C1C1E;
        --mac-text2:      #374151;
        --mac-muted:      #4B5563;
        --mac-bg:         #FAFAF8;
        --mac-border:     rgba(0,0,0,0.07);
        --mac-sep:        rgba(0,0,0,0.04);
    }

    .mac-page { font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'SF Pro Text', 'Helvetica Neue', sans-serif; }

    /* ── Header ─────────────────────────────────── */
    .mac-header h2 { font-size: 1.65rem; font-weight: 700; letter-spacing: -0.02em; color: var(--mac-text); }
    .mac-breadcrumb { font-size: 0.8rem; color: var(--mac-muted); }
    /* Underlined by default (not just on hover) — a link inside plain breadcrumb text must be
       distinguishable without relying on color alone (WCAG 1.4.1). */
    .mac-breadcrumb a { color: var(--mac-primary-tx); text-decoration: underline; text-underline-offset: 2px; }
    .mac-breadcrumb a:hover { text-decoration: underline; }
    .mac-breadcrumb .sep { margin: 0 6px; color: #D1D5DB; }

    /* ── Buttons ─────────────────────────────────── */
    .mac-btn-primary {
        background: var(--mac-primary-hv); color: #fff; border: none; border-radius: 980px;
        padding: 8px 20px; font-size: 0.82rem; font-weight: 500; letter-spacing: -0.01em;
        transition: all 0.18s; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
    }
    .mac-btn-primary:hover { background: var(--mac-primary-tx); color: #fff; transform: scale(1.02); }

    .mac-btn-secondary {
        background: rgba(0,0,0,0.05); color: var(--mac-text); border: 1px solid var(--mac-border); border-radius: 980px;
        padding: 8px 20px; font-size: 0.82rem; font-weight: 500; letter-spacing: -0.01em;
        transition: all 0.18s; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
    }
    .mac-btn-secondary:hover { background: rgba(0,0,0,0.09); color: var(--mac-text); }

    .mac-btn-danger {
        background: rgba(239,68,68,0.08); color: #B91C1C; border: 1px solid rgba(239,68,68,0.15); border-radius: 980px;
        padding: 8px 20px; font-size: 0.82rem; font-weight: 500; letter-spacing: -0.01em;
        transition: all 0.18s; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
    }
    .mac-btn-danger:hover { background: #B91C1C; color: #fff; }

    .mac-btn-success {
        background: rgba(52,199,89,0.10); color: #166534; border: 1px solid rgba(52,199,89,0.20); border-radius: 980px;
        padding: 8px 20px; font-size: 0.82rem; font-weight: 500; letter-spacing: -0.01em;
        transition: all 0.18s; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
    }
    .mac-btn-success:hover { background: #22C55E; color: #fff; border-color: #22C55E; }

    .mac-btn-warning {
        background: rgba(245,158,11,0.10); color: #92400E; border: 1px solid rgba(245,158,11,0.22); border-radius: 980px;
        padding: 8px 20px; font-size: 0.82rem; font-weight: 500; letter-spacing: -0.01em;
        transition: all 0.18s; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
    }
    .mac-btn-warning:hover { background: #F59E0B; color: #fff; border-color: #F59E0B; }

    /* ── Filter bar ──────────────────────────────── */
    .mac-filter-bar {
        background: rgba(255,255,255,0.85); backdrop-filter: blur(20px) saturate(160%);
        -webkit-backdrop-filter: blur(20px) saturate(160%);
        border: 1px solid var(--mac-border); border-radius: 14px; padding: 14px 18px; margin-bottom: 16px;
    }
    .mac-input, .mac-select {
        background: rgba(0,0,0,0.04); border: 1px solid var(--mac-border); border-radius: 10px;
        padding: 9px 14px; font-size: 0.85rem; line-height: 1.5; height: 43px; box-sizing: border-box;
        color: var(--mac-text); transition: all 0.18s; width: 100%; outline: none;
    }
    .mac-input:focus, .mac-select:focus {
        background: #fff; border-color: var(--mac-primary); box-shadow: 0 0 0 3px var(--mac-primary-rg);
    }
    .mac-input::placeholder { color: var(--mac-muted); }
    .mac-filter-btn {
        background: var(--mac-primary-hv); color: #fff; border: none; border-radius: 10px;
        padding: 7px 16px; font-size: 0.82rem; font-weight: 500; cursor: pointer; transition: all 0.15s;
    }
    .mac-filter-btn:hover { background: var(--mac-primary-tx); }
    .mac-clear-btn {
        background: rgba(0,0,0,0.05); color: var(--mac-text); border: 1px solid var(--mac-border); border-radius: 10px;
        padding: 7px 16px; font-size: 0.82rem; font-weight: 500; text-decoration: none; transition: all 0.15s;
    }
    .mac-clear-btn:hover { background: rgba(0,0,0,0.09); color: var(--mac-text); }

    /* ── Card ────────────────────────────────────── */
    /* Opaque, not frosted. The Frosted-Chrome rule puts blur on chrome
       and never on content, and a card is content. The blur also bought nothing
       here: 85% white over the #FAFAF8 page background composites to #FEFEFE —
       one step off solid white per channel — so every admin card was paying for
       a backdrop-filter that is invisible. The filter bar keeps its blur; it is
       chrome and it sits over scrolling content. */
    .mac-card {
        background: #FFFFFF;
        border: 1px solid var(--mac-border); border-radius: 14px; overflow: hidden;
    }
    .mac-card-padded { padding: 24px; }
    .mac-card-header {
        padding: 16px 20px; border-bottom: 1px solid var(--mac-border); background: rgba(0,0,0,0.02);
    }
    .mac-card-title {
        font-size: 0.95rem; font-weight: 600; color: var(--mac-text); letter-spacing: -0.01em;
        display: flex; align-items: center; gap: 8px;
    }

    /* ── Table ───────────────────────────────────── */
    .mac-table { width: 100%; border-collapse: separate; border-spacing: 0; }
    .mac-table thead th {
        background: rgba(0,0,0,0.025); padding: 10px 16px; font-size: 0.72rem; font-weight: 600;
        text-transform: uppercase; letter-spacing: 0.05em; color: var(--mac-muted);
        border-bottom: 1px solid var(--mac-border); white-space: nowrap;
    }
    .mac-table tbody td {
        padding: 14px 16px; font-size: 0.85rem; color: var(--mac-text);
        border-bottom: 1px solid var(--mac-sep); vertical-align: middle;
    }
    .mac-table tbody tr:last-child td { border-bottom: none; }
    .mac-table tbody tr:hover td { background: var(--mac-primary-lt); }

    /* ── Status pills ─────────────────────────────── */
    .mac-status {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 4px 10px; border-radius: 980px; font-size: 0.72rem; font-weight: 600; letter-spacing: 0.01em;
    }
    .mac-status-dot { width: 7px; height: 7px; border-radius: 50%; display: inline-block; }
    .mac-status-green  { background: rgba(52,199,89,0.12);  color: #166534; }
    .mac-status-green .mac-status-dot  { background: #22C55E; }
    .mac-status-gray   { background: rgba(156,163,175,0.15); color: #52525B; }
    .mac-status-gray .mac-status-dot   { background: #9CA3AF; }
    .mac-status-orange { background: rgba(217,119,6,0.12);  color: #92400E; }
    .mac-status-orange .mac-status-dot { background: #D97706; }
    .mac-status-red    { background: rgba(239,68,68,0.10);  color: #991B1B; }
    .mac-status-red .mac-status-dot    { background: #EF4444; }
    .mac-status-blue   { background: rgba(59,130,246,0.10); color: #1D4ED8; }
    .mac-status-blue .mac-status-dot   { background: #3B82F6; }
    .mac-status-purple { background: rgba(139,92,246,0.10); color: #5B21B6; }
    .mac-status-purple .mac-status-dot { background: #8B5CF6; }
    /* Amber/yellow status variant */
    .mac-status-yellow { background: rgba(217,119,6,0.12);  color: #92400E; }
    .mac-status-yellow .mac-status-dot { background: #D97706; }

    /* ── Action buttons ──────────────────────────── */
    .mac-action-btn {
        padding: 5px 12px; border-radius: 8px; font-size: 0.76rem; font-weight: 500;
        border: 1px solid var(--mac-border); background: #fff; color: var(--mac-primary-tx);
        text-decoration: none; transition: border-color 0.15s; display: inline-flex; align-items: center; gap: 4px;
    }
    .mac-action-btn:hover { background: var(--mac-primary-hv); color: #fff; border-color: var(--mac-primary-hv); }
    .mac-action-btn-danger {
        padding: 5px 12px; border-radius: 8px; font-size: 0.76rem; font-weight: 500;
        border: 1px solid rgba(239,68,68,0.2); background: #fff; color: #B91C1C;
        text-decoration: none; transition: all 0.15s; display: inline-flex; align-items: center; gap: 4px;
    }
    .mac-action-btn-danger:hover { background: #B91C1C; color: #fff; border-color: #B91C1C; }

    /* An action button standing in a form row rather than a table row: matches
       the 43px field height so the row reads as one baseline. The base class
       stays table-sized — fourteen other views rely on it. */
    .mac-action-btn-field { height: 43px; padding: 0 14px; border-radius: 10px; justify-content: center; }

    /* A disabled button still looked clickable — every mac button is custom-painted,
       so the browser's default :disabled styling never showed through. */
    .mac-btn-primary:disabled, .mac-btn-secondary:disabled, .mac-btn-danger:disabled,
    .mac-btn-success:disabled, .mac-action-btn:disabled, .mac-action-btn-danger:disabled {
        opacity: 0.4; cursor: not-allowed;
        pointer-events: none; /* else the :hover fill still lights up on a dead button */
    }

    /* ── Form elements ───────────────────────────── */
    .mac-label {
        font-size: 0.78rem; font-weight: 600; color: var(--mac-muted); text-transform: uppercase;
        letter-spacing: 0.04em; margin-bottom: 6px; display: block;
    }
    .mac-form-control {
        background: rgba(0,0,0,0.04); border: 1px solid var(--mac-border); border-radius: 10px;
        padding: 9px 14px; font-size: 0.85rem; line-height: 1.5; height: 43px; box-sizing: border-box;
        color: var(--mac-text); transition: all 0.18s; width: 100%; outline: none;
    }
    textarea.mac-form-control { height: auto; min-height: 80px; }
    .mac-form-control:focus {
        background: #fff; border-color: var(--mac-primary); box-shadow: 0 0 0 3px var(--mac-primary-rg);
    }
    .mac-form-control::placeholder { color: var(--mac-muted); }
    textarea.mac-form-control { resize: vertical; min-height: 80px; }

    /* Bootstrap sizes an input-group's field with `.form-control`, which we do
       not use — so mac-form-control kept its width:100%, filled the group on
       its own and pushed the suffix onto a second line. These give it the same
       flex behaviour and join the two halves into one control.

       nowrap matters as much as the sizing: Bootstrap lets .input-group wrap,
       so in a narrow column the addon still dropped below the field even once
       the widths were right. An addon belongs beside its input or nowhere. */
    .input-group { flex-wrap: nowrap; }
    .input-group > .mac-form-control { flex: 1 1 auto; width: 1%; min-width: 0; }
    .input-group > .mac-form-control:not(:last-child) {
        border-top-right-radius: 0; border-bottom-right-radius: 0; border-right: 0;
    }
    .input-group > .mac-form-control:not(:first-child) {
        border-top-left-radius: 0; border-bottom-left-radius: 0;
    }
    .input-group > .input-group-text {
        height: 43px; padding: 9px 12px; font-size: 0.85rem; line-height: 1.5;
        background: rgba(0,0,0,0.04); border: 1px solid var(--mac-border);
        border-radius: 10px; color: var(--mac-muted); white-space: nowrap;
    }
    .input-group > .input-group-text:not(:first-child) {
        border-top-left-radius: 0; border-bottom-left-radius: 0;
    }
    .input-group > .input-group-text:not(:last-child) {
        border-top-right-radius: 0; border-bottom-right-radius: 0; border-right: 0;
    }

    /* ── Detail rows ─────────────────────────────── */
    .mac-detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0; }
    .mac-detail-row {
        display: flex; justify-content: space-between; align-items: flex-start;
        padding: 12px 20px; border-bottom: 1px solid var(--mac-sep);
    }
    .mac-detail-row:last-child { border-bottom: none; }
    .mac-detail-label { font-size: 0.82rem; font-weight: 500; color: var(--mac-muted); flex-shrink: 0; min-width: 140px; }
    .mac-detail-value { font-size: 0.85rem; color: var(--mac-text); text-align: right; font-weight: 500; }

    /* ── Section heading inside cards ────────────── */
    .mac-section-title {
        font-size: 0.72rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;
        color: var(--mac-muted); padding: 16px 20px 8px; border-top: 1px solid var(--mac-sep);
    }
    .mac-section-title:first-child { border-top: none; }

    /* ── Empty state ─────────────────────────────── */
    .mac-empty { text-align: center; padding: 48px 24px; color: var(--mac-muted); }
    .mac-empty-icon { font-size: 2.5rem; margin-bottom: 12px; opacity: 0.35; }
    .mac-empty-text { font-size: 0.9rem; font-weight: 500; }

    /* ── Pagination ──────────────────────────────── */
    .mac-card .pagination, .mac-page .pagination { padding: 12px 16px; margin: 0; justify-content: center; }
    .mac-card .page-link, .mac-page .page-link {
        border: none; border-radius: 8px; color: var(--mac-primary-tx);
        font-size: 0.82rem; padding: 6px 12px; margin: 0 2px;
    }
    .mac-card .page-link:hover, .mac-page .page-link:hover { background: var(--mac-primary-lt); }
    .mac-card .page-item.active .page-link, .mac-page .page-item.active .page-link {
        background: var(--mac-primary-hv); color: #fff;
    }
    .mac-card .page-item.disabled .page-link, .mac-page .page-item.disabled .page-link { color: #D1D5DB; }

    /* ── Alerts ──────────────────────────────────── */
    /* Bootstrap's 1rem block padding is sized for 1rem text; at 0.85rem it
       leaves a band of empty space above and below the one line of flash text.
       The right padding still has to clear the dismiss button, whose own
       padding is trimmed to match — otherwise it props the alert back open. */
    .mac-page .alert {
        border-radius: 12px; font-size: 0.85rem; border: none;
        padding: 10px 44px 10px 14px;
    }
    .mac-page .alert-dismissible .btn-close { padding: 12px 15px; }

    /* ── Tabs ────────────────────────────────────── */
    .mac-tabs {
        display: flex; list-style: none; margin: 0; padding: 0 20px; gap: 0;
        border-bottom: 1px solid var(--mac-border);
    }
    .mac-tabs .nav-link {
        padding: 12px 18px; font-size: 0.85rem; font-weight: 500; color: var(--mac-muted);
        border: none; border-bottom: 2px solid transparent; background: none;
        cursor: pointer; transition: all 0.15s; margin-bottom: -1px; border-radius: 0; letter-spacing: -0.01em;
    }
    .mac-tabs .nav-link:hover { color: var(--mac-text); background: var(--mac-primary-lt); }
    .mac-tabs .nav-link.active {
        color: var(--mac-primary-tx); border-bottom-color: var(--mac-primary); font-weight: 600; background: none;
    }
    .mac-tabs .nav-link .badge { font-size: 0.65rem; padding: 2px 6px; vertical-align: middle; }

    /* ── Badges (inline count pills) ─────────────── */
    .mac-badge {
        display: inline-flex; align-items: center; padding: 2px 8px;
        border-radius: 980px; font-size: 0.72rem; font-weight: 600; letter-spacing: 0.01em;
    }
    .mac-badge-green  { background: rgba(52,199,89,0.12);  color: #166534; }
    .mac-badge-orange { background: rgba(217,119,6,0.12);  color: #92400E; }
    .mac-badge-red    { background: rgba(239,68,68,0.10);  color: #991B1B; }
    .mac-badge-blue   { background: rgba(59,130,246,0.10); color: #1D4ED8; }
    .mac-badge-gray   { background: rgba(156,163,175,0.15); color: #52525B; }

    /* ── Misc ────────────────────────────────────── */
    .mac-muted   { color: var(--mac-muted); font-size: 0.82rem; }
    .mac-text-sm { font-size: 0.78rem; }
    .mac-divider { border: none; border-top: 1px solid var(--mac-sep); margin: 0; }

    /* Bootstrap's default .text-warning (#ffc107) only reaches ~1.6:1 against white — far
       under WCAG AA's 4.5:1. Several admin views use it ad-hoc for "inherited from elsewhere"
       style notes; override globally here rather than patching each call site. */
    .mac-page .text-warning { color: #92400E !important; }

    /* ── Mobile ──────────────────────────────────── */
    /* Table action rows (View/Approve/Reject/etc. buttons) are right-aligned via
       justify-content-end inside a colspan row spanning the full (often wider-than-viewport)
       table width. On a narrow screen that pushes the buttons off the visible edge of the
       .table-responsive scroll area — reachable only via a non-obvious horizontal swipe,
       and Playwright's actionability auto-scroll doesn't reliably reach them either. Wrapping
       and left-aligning keeps them inside the initially-visible scroll position. */
    @media (max-width: 767.98px) {
        /* ── Admin tables stack into cards ──────────────────────────────────
           WHY THIS EXISTS, measured rather than assumed: the registrations
           table renders 1231px wide inside a 345px scroller on a 393px
           device. Reaching the per-row controls — which sit in the far-right
           columns — means scrolling right through eight columns, and in that
           state a tap lands on whatever the scroller has under the finger
           rather than the button. Three mobile specs failed that way with
           "element is visible, enabled and stable ... intercepts pointer
           events", which is the browser saying the control is on the page and
           not reachable.

           NOT the flex-wrap rule below. That was the cheap suspect and it was
           tested: disabling it changed nothing, two specs still failed and the
           interception was still reported. The width is the cause.

           LABELS ARE OPTIONAL ON PURPOSE. A stacked cell loses its column
           header, so a bare "3x3" reads as nothing. The fix is
           `data-label` on the <td>, and it is opt-in: a view that adds it gets
           the label, a view that has not been touched still stacks and is
           still usable. Twenty-four admin views carry .table-responsive and
           none of them had to change for this rule to help. Add data-label
           where a cell is meaningless without it, not everywhere. */
        .mac-table thead { display: none; }

        .mac-table,
        .mac-table tbody { display: block; width: 100%; }

        .mac-table tbody tr {
            display: block;
            background: #FFFFFF;
            border: 1px solid var(--mac-border);
            border-radius: 12px;
            margin-bottom: 10px;
        }

        .mac-table tbody td {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            width: auto;
            padding: 10px 14px;
            border-bottom: 1px solid var(--mac-sep);
        }

        .mac-table tbody tr > td:last-child { border-bottom: 0; }

        .mac-table tbody td[data-label]::before {
            content: attr(data-label);
            flex: 0 0 38%;
            font-size: 0.72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--mac-muted);
        }

        /* A colspan cell is a full-width band inside the card (the rejection
           history strip, the per-row summary), not a label/value pair. */
        .mac-table tbody td[colspan] { display: block; }

        /* Once rows stack there is nothing left to scroll sideways, and a
           scroller that still clips would re-create the problem this rule
           exists to remove. Scoped with :has() so only stacked tables lose
           their overflow — other .table-responsive users are untouched. */
        .table-responsive:has(.mac-table) { overflow-x: visible; }

        .table-responsive .d-flex.justify-content-end {
            flex-wrap: wrap;
            justify-content: flex-start !important;
        }

        /* Section-header rows (title + a non-wrapping button group pushed to the far edge via
           justify-content-between) overflow the same way — a 3-button toolbar next to a heading
           has nowhere to go but off the right edge of a 375-420px viewport. Wrapping drops the
           button group to its own line under the title instead of clipping it. */
        .mac-page .d-flex.justify-content-between {
            flex-wrap: wrap;
            row-gap: 8px;
        }

        /* WRAPPING THE HEADER IS NOT ENOUGH ON ITS OWN. The rule above drops a
           section header's button group onto its own line, but the group is
           itself a nowrap flex box — on the registrations page it is five
           buttons (Select All, Save Selected Codes, Mark Selected as Ready,
           Grant and Remove Payment Waiver) measuring 590px as one unbreakable
           block. Wrapping its parent moves it down; it does not make it fit.

           MEASURED, and the cost is not cosmetic. 590px inside a 393px device
           pushes the document to 614px, so Chromium zooms out and the LAYOUT
           viewport becomes 614x1136 while the VISUAL viewport stays 393x727 at
           scale 1. document.elementFromPoint then works in layout coordinates
           and correctly reports a button on top, while input is dispatched in
           visual-viewport coordinates where the same y sits past the bottom
           edge. Playwright reports "<td> ... intercepts pointer events" about an
           element covering nothing at all, and no CSS on the table can fix it.
           Four hypotheses died on that symptom — the flex-wrap rule below, table
           width, body overflow, and Bootstrap's smooth scrolling — before this
           toolbar was measured. If a mobile click failure ever names an
           innocent-looking interceptor again, measure innerWidth first. */
        .mac-page .d-flex.justify-content-between > .d-flex {
            flex-wrap: wrap;
        }
    }

    /* ── Keyboard focus ──────────────────────────────
       The user portal has had a designed focus ring on .ap-btn for a while;
       the admin portal was still falling back to the browser's default blue
       one, which reads as foreign against an amber UI and varies per browser.

       outline rather than box-shadow on purpose: several admin controls are
       transparent at rest (.sidebar-toggle, .mac-action-btn), and a
       translucent shadow on a transparent button can disappear into whatever
       is behind it. An outline always renders.

       :focus-visible, not :focus — mouse users should not see a ring on
       every click, only keyboard and other non-pointer input. */
    .mac-btn-primary:focus-visible,
    .mac-btn-secondary:focus-visible,
    .mac-btn-danger:focus-visible,
    .mac-btn-success:focus-visible,
    .mac-btn-warning:focus-visible,
    .mac-action-btn:focus-visible,
    .mac-action-btn-danger:focus-visible,
    .mac-filter-btn:focus-visible,
    .mac-clear-btn:focus-visible,
    .mac-tabs .nav-link:focus-visible {
        outline: 2px solid var(--mac-primary);
        outline-offset: 2px;
    }

    /* ── Reduced motion ──────────────────────────────
       Deliberately narrow: only the spatial transform is dropped. The colour
       and background transitions on buttons, rows and tabs stay, because they
       are how this UI confirms hover/focus/active state — killing them all
       with a blanket 0.01ms rule would remove feedback, not just motion. */
    @media (prefers-reduced-motion: reduce) {
        .mac-btn-primary:hover {
            transform: none;
        }
        .mac-btn-primary,
        .mac-btn-secondary,
        .mac-btn-danger,
        .mac-btn-success,
        .mac-btn-warning,
        .mac-input,
        .mac-select,
        .mac-form-control {
            transition-property: background-color, border-color, color, box-shadow;
        }
    }

/* Labelled premise fields on a company page. Two columns where there is room,
   one where there is not — a fax number and a postcode both need their name
   attached, and a run-together block only reads as an address. */
.mac-addr-fields {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(11rem, 1fr));
    gap: 8px 18px;
    margin: 0;
}
.mac-addr-fields dt {
    font-size: 0.68rem;
    font-weight: 600;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #6B7280;
    margin-bottom: 1px;
}
.mac-addr-fields dd {
    font-size: 0.82rem;
    color: #1C1C1E;
    margin: 0;
    overflow-wrap: anywhere;
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
                });
            });
        });
    });
</script>
