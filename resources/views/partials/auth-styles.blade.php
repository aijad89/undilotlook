{{--
    The two sign-in screens (participant and admin) share one look: a navy
    panel with the mark, and the form on white. The form views use Bootstrap's
    own classes; they are restyled here, inside .login-form-panel only.
--}}
<style>
    * { box-sizing: border-box; }
    html, body { height: 100%; margin: 0; padding: 0; background: var(--ui-surface); }

    .login-wrapper { display: flex; min-height: 100vh; }

    .login-panel {
        position: relative; flex: 1; display: flex; flex-direction: column; justify-content: center;
        padding: 64px clamp(32px, 6vw, 96px); background: var(--ui-navy-900); color: #fff; text-align: left;
    }
    .login-panel.has-bg { background-image: var(--login-bg); background-size: cover; background-position: center; }
    .login-panel-overlay { position: absolute; inset: 0; background: rgba(7, 26, 51, 0.72); }
    .login-panel:not(.has-bg) .login-panel-overlay { display: none; }
    .login-panel-content { position: relative; z-index: 1; max-width: 520px; }
    .login-mark { width: 56px; height: 56px; display: grid; place-items: center; margin-bottom: 28px; border-radius: 14px; background: var(--ui-gold); color: var(--ui-navy-950); font-size: 1.6rem; }
    .login-panel-title { font-size: clamp(2rem, 3.4vw, 2.8rem); font-weight: 700; line-height: 1.12; letter-spacing: -0.02em; }
    .login-panel-subtitle { margin-top: 12px; font-size: 1.15rem; line-height: 1.5; color: var(--ui-on-navy-2); }

    .login-form-panel { width: 480px; flex-shrink: 0; display: flex; flex-direction: column; justify-content: center; padding: 64px 56px; background: var(--ui-surface); }
    .login-form-panel h1, .login-form-panel h2 { color: var(--ui-text) !important; letter-spacing: -0.015em; }
    .login-form-panel .text-muted { color: var(--ui-muted) !important; font-size: 1rem !important; }
    .login-form-panel .form-label { font-size: 0.95rem; font-weight: 600; color: var(--ui-text-2); }
    .login-form-panel .form-control {
        min-height: 48px; padding: 10px 14px; border: 1px solid var(--ui-border-strong); border-radius: var(--ui-r);
        font-size: 1rem; color: var(--ui-text); background: var(--ui-surface);
    }
    .login-form-panel .form-control:hover { border-color: #a9b4c3; }
    .login-form-panel .form-control:focus { border-color: var(--ui-blue); box-shadow: 0 0 0 3px rgba(26, 86, 219, 0.18); }
    .login-form-panel .form-control.is-invalid { border-color: var(--ui-bad); }
    .login-form-panel .invalid-feedback { color: var(--ui-bad-text); font-size: 0.92rem; }
    .login-form-panel .form-check-input { width: 1.25em; height: 1.25em; border-color: var(--ui-border-strong); }
    .login-form-panel .form-check-input:checked { background-color: var(--ui-blue); border-color: var(--ui-blue); }
    .login-form-panel .btn-primary {
        min-height: 52px; border: none; border-radius: var(--ui-r); background: var(--ui-blue);
        font-size: 1.05rem; font-weight: 600; box-shadow: var(--ui-shadow-1), inset 0 -2px 0 rgba(0, 0, 0, 0.15);
    }
    .login-form-panel .btn-primary:hover { background: var(--ui-blue-hover); }
    .login-form-panel .btn-primary:active { background: var(--ui-blue-press) !important; }
    .login-form-panel a { color: var(--ui-blue-text); font-weight: 600; }
    .login-form-panel .alert { border: 1px solid transparent; border-radius: var(--ui-r-lg); font-size: 0.95rem; }
    .login-form-panel .alert-success { background: var(--ui-ok-tint); border-color: #b9e1ca; color: var(--ui-ok-text); }
    .login-form-panel .alert-danger { background: var(--ui-bad-tint); border-color: #f5c3b6; color: var(--ui-bad-text); }
    /* The two reset screens were built as a Bootstrap card; on this layout they
       are the panel's own content, so the card's chrome falls away. */
    .login-form-panel .container { max-width: none; margin: 0 !important; padding: 0; }
    .login-form-panel .container > .row { margin: 0; }
    .login-form-panel .container .col-md-6 { width: 100%; padding: 0; }
    .login-form-panel .card { border: none !important; box-shadow: none !important; }
    .login-form-panel .card-header { padding: 0 0 16px !important; background: none !important; color: var(--ui-text) !important; text-align: left !important; font-size: 1.5rem; font-weight: 700; }
    .login-form-panel .card-body { padding: 0; }

    @media (max-width: 860px) {
        .login-wrapper { flex-direction: column; }
        .login-panel { flex: none; min-height: 0; padding: 32px 24px; }
        .login-mark { width: 44px; height: 44px; margin-bottom: 16px; font-size: 1.25rem; }
        .login-panel-title { font-size: 1.6rem; }
        .login-panel-subtitle { font-size: 1rem; }
        .login-form-panel { width: 100%; padding: 32px 24px 48px; }
    }
</style>
