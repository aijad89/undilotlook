@extends('layouts.frontend')

@section('title', 'Lot Draw')
@section('page-title', 'Lot Draw')

@section('content')
@include('partials.apple-styles')

<style>
    /* The lot page: one decision, made once, then its record. */
    /* Same column as the rest of the portal. With room, the record sits left
       and what to do with it (code, slip) sits right; on a phone, one column. */
    .lot-grid { display: grid; gap: 16px; align-items: start; }
    .lot-grid > * > .ap-card { margin-bottom: 16px; }
    .lot-side .text-center { text-align: center; }
    .lot-split { display: grid; gap: 8px 40px; }
    @media (min-width: 992px) {
        .lot-grid { grid-template-columns: minmax(0, 3fr) minmax(300px, 2fr); gap: 24px; }
        .lot-side { position: sticky; top: calc(var(--topbar-h) + 24px); }
        .lot-split { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); align-items: start; }
        .lot-split .lot-rules { margin-bottom: 0; }
    }
    .lot-page .ui-stepper { margin: 4px 0 24px; }
    .lot-page .ui-stepper .label { font-size: 0.875rem; }
    @media (max-width: 576px) { .lot-page .ap-detail-row.align-items-center { align-items: flex-start !important; } .lot-page .lot-where { text-align: left; } }
    .lot-fact { display: flex; align-items: center; justify-content: space-between; gap: 14px; padding: 18px 20px; border-radius: var(--ui-r-lg); background: var(--ui-surface-2); border: 1px solid var(--ui-sep); }
    .lot-fact-label { font-size: 1rem; color: var(--ui-muted); }
    .lot-fact-value { font-size: 1.5rem; font-weight: 700; letter-spacing: 0.01em; color: var(--ui-text); }
    .lot-fact-value.is-missing { font-size: 1.125rem; color: var(--ui-bad-text); }
    .lot-rules { margin: 18px 0; padding: 0; list-style: none; display: grid; gap: 12px; }
    .lot-rules li { display: flex; gap: 12px; align-items: flex-start; font-size: 1rem; color: var(--ui-text-2); }
    .lot-rules i { flex-shrink: 0; margin-top: 2px; font-size: 1.125rem; color: var(--ui-blue); }
    .lot-agree { display: flex; gap: 12px; align-items: flex-start; margin: 0 0 16px; padding: 16px; border: 2px solid var(--ui-border); border-radius: var(--ui-r-lg); cursor: pointer; transition: border-color var(--ui-fast) var(--ui-ease), background-color var(--ui-fast) var(--ui-ease); }
    .lot-agree:has(input:checked) { border-color: var(--ui-blue); background: var(--ui-blue-tint); }
    .lot-agree .form-check-input { flex-shrink: 0; float: none; margin: 1px 0 0; }
    .lot-agree span { font-size: 1rem; font-weight: 600; color: var(--ui-text); }
    .lot-note { display: flex; gap: 10px; margin-top: 14px; font-size: 1rem; color: var(--ui-muted); }
    .lot-done-head { text-align: center; padding: 6px 0 4px; }
    .lot-done-icon { width: 64px; height: 64px; margin: 0 auto 12px; display: grid; place-items: center; border-radius: 50%; background: var(--ui-ok-tint); color: var(--ui-ok); font-size: 2.25rem; }
    .lot-done-head h3 { margin: 0 0 4px; font-size: 1.5rem; font-weight: 700; color: var(--ui-text); }
    .lot-done-head p { margin: 0; font-size: 1rem; color: var(--ui-muted); }
    .lot-code { display: inline-flex; align-items: center; gap: 8px; padding: 6px 12px; border-radius: var(--ui-r); background: var(--ui-gold-tint); color: var(--ui-gold-text); font-size: 1.125rem; font-weight: 700; }
    .lot-code i { color: var(--ui-gold); }
    .lot-where { display: block; margin-top: 4px; font-size: 0.875rem; font-weight: 500; color: var(--ui-muted); text-align: right; }
    .lot-qr { text-align: center; }
    .lot-qr img { width: 168px; height: 168px; image-rendering: pixelated; }
    .lot-qr-code { margin-top: 8px; font-family: var(--ui-font-mono); font-size: 1rem; letter-spacing: 3px; color: var(--ui-text); }
    .lot-qr-empty { width: 168px; height: 168px; margin: 0 auto; display: grid; place-items: center; border: 2px dashed var(--ui-border-strong); border-radius: var(--ui-r-lg); color: var(--ui-border-strong); font-size: 2.25rem; }
    .lot-actions { display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; margin: 18px 0; }
    @media (max-width: 576px) { .lot-actions .ap-btn { flex: 1 1 100%; } }
</style>

<div class="ap-page lot-page">
    <div class="ap-page-header">
        <div>
            <h2 class="ap-page-title">{{ $event->title }}</h2>
            <p class="ap-page-subtitle">Lot Draw</p>
        </div>
    </div>

    @include('partials.draw-stepper', ['status' => $event->polling_status])

    @if($errors->any())
        <div class="ap-alert ap-alert-danger" role="alert">
            <i class="bi bi-exclamation-triangle ap-alert-icon" aria-hidden="true"></i>
            <div>
                <strong>Validation Errors:</strong>
                <ul class="mb-0 mt-1 ps-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif
    @if(session('success'))
        <div class="ap-alert ap-alert-success" role="status">
            <i class="bi bi-check-circle ap-alert-icon" aria-hidden="true"></i><div>{{ session('success') }}</div>
        </div>
    @endif
    @if(session('error'))
        <div class="ap-alert ap-alert-danger" role="alert">
            <i class="bi bi-exclamation-triangle ap-alert-icon" aria-hidden="true"></i><div>{{ session('error') }}</div>
        </div>
    @endif

    @if($hasVoted ?? false)
        {{-- Already voted --}}
        <div class="lot-grid">
        <div class="lot-main">
        <div class="ap-card ap-card-body-lg mb-3">
            <div class="lot-done-head">
                <div class="lot-done-icon" aria-hidden="true"><i class="bi bi-check-lg"></i></div>
                <h3>Your Lot Is Confirmed!</h3>
                <p>Thank you for confirming your participation.</p>
            </div>

            @if(session('just_drawn') && isset($assignedPlot) && $assignedPlot)
                @include('zvote.partials.lot-reveal', [
                    'lotCode' => $assignedPlot->plot_code,
                    'style'   => $event->drawAnimation(),
                ])
            @endif

            @if(isset($poll) && $poll)
                <div class="mt-3">
                    <div class="ap-detail-row">
                        <span class="ap-detail-label">Craft Category Code</span>
                        <span class="ap-detail-value">{{ $poll->code ?? $registration->code ?? 'N/A' }}</span>
                    </div>
                    <div class="ap-detail-row align-items-center">
                        <span class="ap-detail-label">Lot No.</span>
                        @if(isset($assignedPlot) && $assignedPlot)
                            <span class="ap-detail-value">
                                <strong class="lot-code" data-lot-code="{{ $assignedPlot->plot_code }}">
                                    <i class="bi bi-geo-alt-fill" aria-hidden="true"></i>{{ $assignedPlot->plot_code }}
                                </strong>
                                <span class="lot-where">
                                    {{ $assignedPlot->plotSize->size ?? '' }}
                                    @if($assignedPlot->plotSize->location ?? false)
                                        | {{ $assignedPlot->plotSize->location }}
                                    @endif
                                </span>
                            </span>
                        @else
                            {{-- Confirming draws the lot, so no lot here means a broken
                                 link, not a wait. --}}
                            <span class="ap-badge ap-badge-red">Not linked — contact admin</span>
                        @endif
                    </div>
                    @if(isset($assignedPlot) && $assignedPlot)
                        <div class="ap-detail-row">
                            <span class="ap-detail-label">How it was allocated</span>
                            {{-- One wording for every participant, true whichever way the
                                 lot came to them: naming a reservation on a participant's own
                                 screen exposes internal operations and reads as preference. --}}
                            <span class="ap-detail-value">
                                By the organiser's lot draw
                                <span class="lot-where">You do not choose your own lot.</span>
                            </span>
                        </div>
                    @endif
                    <div class="ap-detail-row">
                        <span class="ap-detail-label">Confirmed On</span>
                        <span class="ap-detail-value">{{ $poll->created_at->format('d M Y, h:i A') }}</span>
                    </div>
                </div>
            @endif
        </div>
        </div>

        <aside class="lot-side">
        @if(isset($assignedPlot) && $assignedPlot)
            {{-- The check-in code, so a participant can be checked in from their
                 phone without printing anything — see User::icHash(). --}}
            @php
                $icQr = auth()->user()->icQrDataUri(200);
                $icHash = auth()->user()->icHash();
            @endphp
            <div class="ap-card ap-card-body-lg lot-qr mb-3">
                @if($icQr)
                    <img src="{{ $icQr }}" alt="Your check-in code" loading="lazy">
                    <div class="lot-qr-code">{{ strtoupper(substr($icHash, 0, 8)) }}</div>
                    <p class="ap-muted mt-1 mb-0">Show this at the counter to check in.</p>
                @else
                    {{-- Without an IC there is nothing to encode. Say what is missing
                         and who can fix it. --}}
                    <div class="lot-qr-empty" aria-hidden="true"><i class="bi bi-qr-code"></i></div>
                    <p class="mt-3 mb-1 fw-semibold">No check-in code yet</p>
                    <p class="ap-muted mb-0">Your IC number is not on file. Ask the office to add it, and the code appears here.</p>
                @endif
            </div>

            {{-- Print opens the slip in the browser's own viewer; share hands the
                 file itself to the OS share sheet. --}}
            <div class="lot-actions">
                <a href="{{ route('zvote.slip', $event) }}" target="_blank" rel="noopener" class="ap-btn ap-btn-secondary">
                    <i class="bi bi-printer" aria-hidden="true"></i>Print Slip
                </a>
                <button type="button" class="ap-btn ap-btn-secondary"
                        data-share-slip="{{ route('zvote.slip', $event) }}"
                        data-slip-name="lot-{{ $assignedPlot->plot_code }}.pdf">
                    <i class="bi bi-share" aria-hidden="true"></i>Share Slip
                </button>
            </div>
        @endif

        <div class="text-center">
            <a href="{{ route('dashboard') }}" class="ap-btn ap-btn-secondary">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>Back to Dashboard
            </a>
        </div>
        </aside>
        </div>

    @else
        {{-- Vote form --}}
        <div class="ap-card ap-card-body-lg mb-3 lot-split">
            <div>
            <div class="lot-fact">
                <span class="lot-fact-label">Allocated Lot Category</span>
                @if($code === 'NOT SET' || ! $code)
                    <span class="lot-fact-value is-missing"><i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i>NOT SET</span>
                @else
                    <span class="lot-fact-value">{{ $code }}</span>
                @endif
            </div>
            @if($code === 'NOT SET' || ! $code)
                <p class="mt-2 mb-0" style="color:var(--ui-bad-text)">Company code has not been assigned. Please contact admin.</p>
            @endif

            {{-- Worded to hold whether the lot was reserved or is still to be
                 drawn: the page is not told which, and "assigned" is true either way. --}}
            <ul class="lot-rules">
                <li>
                    <i class="bi bi-gift" aria-hidden="true"></i>
                    <span><strong>Lot Draw</strong> — Confirming assigns you one lot from
                        @if($code && $code !== 'NOT SET')
                            category <strong>{{ $code }}</strong>
                        @else
                            your craft category
                        @endif
                        and reveals it straight away.</span>
                </li>
                <li>
                    <i class="bi bi-info-circle" aria-hidden="true"></i>
                    <span>You do not choose which lot, and you can only confirm once.</span>
                </li>
            </ul>
            </div>

            <div>
            <form action="{{ route('zvote.submit', $event) }}" method="POST">
                @csrf

                <label class="lot-agree" for="agree">
                    <input class="form-check-input" type="checkbox" name="agreement" id="agree" value="1" required
                        {{ $code === 'NOT SET' ? 'disabled' : '' }}>
                    <span>I agree to this lot allocation and confirm my participation</span>
                </label>

                <button type="submit" class="ap-btn ap-btn-primary ap-btn-lg w-100"
                    {{ $code === 'NOT SET' ? 'disabled' : '' }}>
                    <i class="bi bi-check-circle" aria-hidden="true"></i>Confirm Lot
                </button>
            </form>

            @if($code === 'NOT SET')
                <div class="ap-alert ap-alert-warning mt-3 mb-0">
                    <i class="bi bi-lock ap-alert-icon" aria-hidden="true"></i>
                    <div><strong>Lot draw locked:</strong> Company code must be set by admin first.</div>
                </div>
            @endif

            <p class="lot-note mb-0">
                <i class="bi bi-clock" aria-hidden="true"></i>
                <span><strong>Lot draw closes:</strong>
                    {{ $event->polling_deadline?->format('d M Y, h:i A') ?? 'Not set' }}
                    &middot; Your lot is final once confirmed.</span>
            </p>
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
// Share the slip itself, not a link to it: the PDF route sits behind auth, so a
// URL is useless to whoever receives it. Web Share with a file is the only way
// the participant's own copy actually travels.
document.querySelector('[data-share-slip]')?.addEventListener('click', async (e) => {
    const btn = e.currentTarget;
    const original = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Preparing…';

    try {
        const res = await fetch(btn.dataset.shareSlip);
        if (!res.ok) throw new Error(res.status);
        const file = new File([await res.blob()], btn.dataset.slipName, { type: 'application/pdf' });

        // Desktop browsers mostly cannot share files. Opening the slip is the
        // honest fallback — from there it can be saved and sent by hand.
        if (navigator.canShare?.({ files: [file] })) {
            await navigator.share({ files: [file], title: 'Lot Draw Slip' });
        } else {
            window.open(btn.dataset.shareSlip, '_blank', 'noopener');
        }
    } catch (err) {
        // AbortError is the participant closing the share sheet, not a failure.
        if (err.name !== 'AbortError') window.open(btn.dataset.shareSlip, '_blank', 'noopener');
    } finally {
        btn.disabled = false;
        btn.innerHTML = original;
    }
});
</script>
@endpush
