{{--
    The one visual system under every screen: admin, participant, public, sign-in.

    Included in the <head> of each layout, before that layout's own chrome, so the
    two component partials (mac-styles for the office, apple-styles for usahawan)
    and every layout read the same tokens. Change a colour here and it changes
    everywhere; nothing else in the app should spell a hex value.

    Benchmarks: GOV.UK / Singpass for plainness and legibility, the Stripe
    Dashboard for the office's tables and forms. The shell is navy, work happens on
    white, one blue acts, Kraftangan gold only marks "yours" and "you are here",
    and focus is GOV.UK's yellow-and-black ring.
--}}
<style>
    :root {
        /* Shell */
        --ui-navy-950: #071a33;
        --ui-navy-900: #0b2240;
        --ui-navy-800: #12305a;
        --ui-navy-700: #1b3f70;
        --ui-on-navy: #ffffff;
        --ui-on-navy-2: #b9c6d8;

        /* Ground and surfaces */
        --ui-ground: #f3f5f8;
        --ui-surface: #ffffff;
        --ui-surface-2: #f8fafc;
        --ui-border: #dce1e8;
        --ui-border-strong: #c3ccd8;
        --ui-sep: #e9edf2;

        /* Text */
        --ui-text: #101828;
        --ui-text-2: #344054;
        --ui-muted: #586477;

        /* Action */
        --ui-blue: #1a56db;
        --ui-blue-hover: #1546b3;
        --ui-blue-press: #103a96;
        --ui-blue-tint: #ebf1fd;
        --ui-blue-text: #1546b3;

        /* Kraftangan gold: "yours" and "you are here", never a button */
        --ui-gold: #e3a92b;
        --ui-gold-tint: #fdf5e1;
        --ui-gold-text: #7a5300;

        /* States: text on tint, all at least 4.5:1 */
        --ui-ok: #12804a;
        --ui-ok-tint: #e6f4ec;
        --ui-ok-text: #0b6239;
        --ui-warn: #d98a00;
        --ui-warn-tint: #fdf2df;
        --ui-warn-text: #7f4500;
        --ui-bad: #c4320a;
        --ui-bad-tint: #fdece8;
        --ui-bad-text: #9a2006;
        --ui-info-tint: var(--ui-blue-tint);
        --ui-info-text: var(--ui-blue-text);
        --ui-ok-border: #b9e1ca;
        --ui-bad-border: #f5c3b6;
        --ui-bad-border-2: #f1b8a8;
        --ui-warn-border: #f3d29a;
        --ui-warn-border-2: #efc98a;
        --ui-info-border: #c4d5f8;
        --ui-blue-tint-2: #d7e3fb;
        --ui-blue-border: #b6c9f4;
        --ui-blue-border-2: #9db8ef;
        --ui-row-hover: #f6f9ff;
        --ui-gold-tint-2: #fbe7bd;
        --ui-border-hover: #a9b4c3;
        --ui-on-navy-3: #d6deea;
        --ui-on-navy-soft: #c3cedd;
        --ui-on-navy-icon: #93a4bb;
        --ui-on-navy-dim: #8fa1ba;
        --ui-neutral-tint: #eef1f5;
        --ui-neutral-text: #475467;
        --ui-violet-tint: #f0edfb;
        --ui-violet-text: #5b3fb3;

        /* Focus, GOV.UK's: yellow, then black, readable on any ground */
        --ui-focus: #ffdd00;
        --ui-focus-ink: #0b0c0c;
        --ui-focus-ring: 0 0 0 3px var(--ui-focus), 0 0 0 5px var(--ui-focus-ink);

        /* Shape and depth */
        --ui-r-sm: 6px;
        --ui-r: 8px;
        --ui-r-lg: 12px;
        --ui-shadow-1: 0 1px 2px rgba(16, 24, 40, 0.05), 0 1px 3px rgba(16, 24, 40, 0.06);
        --ui-shadow-2: 0 2px 4px -2px rgba(16, 24, 40, 0.06), 0 8px 16px -4px rgba(16, 24, 40, 0.10);
        --ui-shadow-pop: 0 4px 6px -2px rgba(16, 24, 40, 0.05), 0 16px 32px -8px rgba(16, 24, 40, 0.18);

        /* Type */
        --ui-font: "Segoe UI Variable Text", "Segoe UI", -apple-system, BlinkMacSystemFont, Roboto, "Helvetica Neue", Arial, sans-serif;
        --ui-font-mono: ui-monospace, "Cascadia Mono", "Segoe UI Mono", Consolas, monospace;

        /* Motion: one curve, exponential ease-out */
        --ui-ease: cubic-bezier(0.16, 1, 0.3, 1);
        --ui-fast: 140ms;
        --ui-slow: 420ms;

        /* Control heights: 40 for the office, 48 for usahawan on a phone */
        --ui-control-h: 40px;
        --ui-control-h-lg: 48px;
    }

    /* ── Browser surfaces ──────────────────────────────────────────────────── */
    html { -webkit-text-size-adjust: 100%; }
    body {
        font-family: var(--ui-font);
        color: var(--ui-text);
        background: var(--ui-ground);
        -webkit-font-smoothing: antialiased;
        text-rendering: optimizeLegibility;
    }
    ::selection { background: #cfdcfa; color: var(--ui-text); }
    input, select, textarea, button { font-family: inherit; caret-color: var(--ui-blue); }
    a { color: var(--ui-blue-text); text-underline-offset: 3px; text-decoration-thickness: 1px; }
    a:hover { color: var(--ui-blue-press); text-decoration-thickness: 2px; }

    /* Every keyboard-reachable thing gets the same ring, on every ground. */
    :focus-visible { outline: none; box-shadow: var(--ui-focus-ring) !important; }
    /* A plain text link takes GOV.UK's highlighter instead: yellow behind, black under. */
    a:not([class]):focus-visible { background: var(--ui-focus); color: var(--ui-focus-ink); text-decoration: none; box-shadow: 0 -2px var(--ui-focus), 0 4px var(--ui-focus-ink) !important; }

    table td, table th, .ui-num, time, .ap-draw-meta, .lot-code, .ap-draw-lot-link { font-variant-numeric: tabular-nums; }

    * { scrollbar-width: thin; scrollbar-color: #b8c2cf transparent; }
    ::-webkit-scrollbar { width: 10px; height: 10px; }
    ::-webkit-scrollbar-thumb { background: #c3ccd8; border-radius: 10px; border: 3px solid transparent; background-clip: padding-box; }
    ::-webkit-scrollbar-thumb:hover { background: #9aa6b6; background-clip: padding-box; border: 3px solid transparent; }
    ::-webkit-scrollbar-track { background: transparent; }

    /* ── Status: icon + word, never colour alone ───────────────────────────── */
    .ui-status {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 2px 10px 2px 8px; min-height: 24px;
        border-radius: 999px; font-size: 0.78rem; font-weight: 600; line-height: 1.2;
        background: var(--ui-neutral-tint); color: var(--ui-neutral-text); white-space: nowrap;
    }

    /* ── The draw-stage stepper ────────────────────────────────────────────── */
    /* Where a draw is, on every draw: the office reads the stage and the next
       move at a glance; a usahawan sees how far there is to go. Four stops, the
       current one ringed, the passed ones filled, the line between them drawn
       to the current stop. Cancelled draws grey the whole track. */
    .ui-stepper { list-style: none; margin: 0; padding: 0; display: flex; align-items: flex-start; gap: 0; min-width: 0; }
    .ui-stepper li { position: relative; flex: 1 1 0; min-width: 0; display: flex; flex-direction: column; align-items: center; text-align: center; gap: 6px; }
    .ui-stepper li::before {
        content: ""; position: absolute; top: 9px; left: calc(-50% + 12px); right: calc(50% + 12px);
        height: 2px; background: var(--ui-border); border-radius: 2px;
    }
    .ui-stepper li:first-child::before { display: none; }
    .ui-stepper .dot {
        position: relative; z-index: 1; width: 20px; height: 20px; border-radius: 50%;
        display: grid; place-items: center; font-size: 0.7rem;
        background: var(--ui-surface); border: 2px solid var(--ui-border-strong); color: transparent;
    }
    .ui-stepper .label { font-size: 0.75rem; font-weight: 600; color: var(--ui-muted); line-height: 1.25; overflow-wrap: anywhere; }
    .ui-stepper li.done::before, .ui-stepper li.current::before { background: var(--ui-blue); }
    .ui-stepper li.done .dot { background: var(--ui-blue); border-color: var(--ui-blue); color: #fff; }
    .ui-stepper li.current .dot { background: var(--ui-surface); border-color: var(--ui-blue); box-shadow: 0 0 0 4px var(--ui-blue-tint); }
    .ui-stepper li.current .dot::after { content: ""; width: 8px; height: 8px; border-radius: 50%; background: var(--ui-blue); }
    .ui-stepper li.current .label { color: var(--ui-text); }
    .ui-stepper li.done .label { color: var(--ui-text-2); }
    .ui-stepper.is-cancelled li::before, .ui-stepper.is-cancelled li.done::before { background: var(--ui-border); }
    .ui-stepper.is-cancelled .dot { background: var(--ui-surface-2) !important; border-color: var(--ui-border) !important; box-shadow: none !important; color: transparent !important; }
    .ui-stepper.is-cancelled .dot::after { display: none; }
    .ui-stepper.is-cancelled .label { color: var(--ui-muted) !important; }

    /* Compact: dots only, for a table cell; the current label sits beside it. */
    .ui-stepper-compact { display: inline-flex; align-items: center; gap: 10px; }
    .ui-stepper-compact .track { display: inline-flex; align-items: center; gap: 4px; }
    .ui-stepper-compact .pip { width: 18px; height: 6px; border-radius: 3px; background: var(--ui-border); }
    .ui-stepper-compact .pip.on { background: var(--ui-blue); }
    .ui-stepper-compact .pip.now { background: var(--ui-blue); box-shadow: 0 0 0 3px var(--ui-blue-tint); }
    .ui-stepper-compact.is-cancelled .pip { background: var(--ui-border); box-shadow: none; }
    .ui-stepper-compact .word { font-size: 0.8rem; font-weight: 600; color: var(--ui-text); white-space: nowrap; }

    /* The one authored moment: the current stop's ring settles in once. */
    @media (prefers-reduced-motion: no-preference) {
        .ui-stepper li.current .dot { animation: ui-stop-in var(--ui-slow) var(--ui-ease) both; }
        @keyframes ui-stop-in { from { box-shadow: 0 0 0 12px rgba(26, 86, 219, 0); } to { box-shadow: 0 0 0 4px var(--ui-blue-tint); } }
    }
</style>
