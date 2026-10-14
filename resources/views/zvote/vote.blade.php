@extends('layouts.frontend')

@section('title', 'Lot Draw')
@section('page-title', 'Lot Draw')

@section('content')
@include('partials.apple-styles')

<div class="ap-page py-2">
    <div class="row justify-content-center">
        <div class="col-lg-7 col-md-9">

            <div class="ap-card">
                <div class="ap-card-header">
                    {{-- .ap-card-title does not exist in the design system; the
                         real class is .ap-card-header-title. The heading on the
                         lot-draw confirmation card was rendering unstyled. --}}
                    <div class="ap-card-header-title">
                        <i class="bi bi-ballot" style="color:#007AFF;"></i> Lot Draw — {{ $event->title }}
                    </div>
                </div>
                <div class="ap-card-body">

                    {{-- Flash alerts --}}
                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show mb-3">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Validation Errors:</strong>
                            <ul class="mb-0 mt-1">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show mb-3">
                            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show mb-3">
                            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if($hasVoted ?? false)
                        {{-- Already voted --}}
                        <div class="text-center py-3">
                            <div class="d-inline-flex align-items-center justify-content-center mb-3"
                                 style="width:60px;height:60px;border-radius:50%;background:rgba(52,199,89,0.12);">
                                <i class="bi bi-check-circle-fill" style="font-size:1.8rem;color:#34C759;"></i>
                            </div>
                            <h5 style="font-weight:700;color:#1C1C1E;margin-bottom:4px;">Your Lot Is Confirmed!</h5>
                            <p style="font-size:0.85rem;color:#8E8E93;margin-bottom:20px;">Thank you for confirming your participation.</p>

                            @if(session('just_drawn') && isset($assignedPlot) && $assignedPlot)
                                @include('zvote.partials.lot-reveal', [
                                    'lotCode' => $assignedPlot->plot_code,
                                    'style'   => $event->drawAnimation(),
                                ])
                            @endif

                            @if(isset($poll) && $poll)
                                <div class="ap-card mb-3" style="text-align:left;">
                                    <div class="ap-card-body-sm">
                                        <div class="d-flex justify-content-between py-2"
                                             style="border-bottom:1px solid rgba(60,60,67,0.07);font-size:0.85rem;">
                                            <span style="color:#8E8E93;">Craft Category Code</span>
                                            <strong>{{ $poll->code ?? $registration->code ?? 'N/A' }}</strong>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center py-2"
                                             style="border-bottom:1px solid rgba(60,60,67,0.07);font-size:0.85rem;">
                                            <span style="color:#8E8E93;">Lot No.</span>
                                            @if(isset($assignedPlot) && $assignedPlot)
                                                <span>
                                                    <strong style="color:#34C759;" data-lot-code="{{ $assignedPlot->plot_code }}">
                                                        <i class="bi bi-geo-alt-fill me-1"></i>{{ $assignedPlot->plot_code }}
                                                    </strong>
                                                    <span style="color:#8E8E93;font-size:0.78rem;margin-left:6px;">
                                                        {{ $assignedPlot->plotSize->size ?? '' }}
                                                        @if($assignedPlot->plotSize->location ?? false)
                                                            | {{ $assignedPlot->plotSize->location }}
                                                        @endif
                                                    </span>
                                                </span>
                                            @else
                                                {{-- Confirming draws the lot, so no lot here means a broken
                                                     link, not a wait. --}}
                                                <span class="ap-badge ap-badge-red">
                                                    <i class="bi bi-exclamation-triangle me-1"></i>Not linked — contact admin
                                                </span>
                                            @endif
                                        </div>
                                        @if(isset($assignedPlot) && $assignedPlot)
                                            <div class="d-flex justify-content-between py-2"
                                                 style="border-bottom:1px solid rgba(60,60,67,0.07);font-size:0.85rem;">
                                                <span style="color:#8E8E93;">How it was allocated</span>
                                                {{-- One wording for every participant, and one that is true
                                                     whichever way the lot came to them.

                                                     Deliberate, and not to be "fixed" back into a branch on
                                                     allocation_type. Reserving is infrastructure — agencies
                                                     mapped so the draw cannot misplace them, NGOs, and lots
                                                     reconciled against the plots actually built.
                                                     Naming it on a participant's own screen exposes internal
                                                     operations and reads as preferential treatment to anyone
                                                     comparing notes. showVote already withholds the same
                                                     thing before confirming; this is the other half of it.

                                                     It says nothing about *how* on purpose. "Drawn at random"
                                                     would have concealed the reservation just as well, but it
                                                     is not true of a reserved lot, and a false line on the
                                                     participant's own record is the wrong thing to be holding
                                                     if an allocation is ever disputed. --}}
                                                <span style="text-align:right;">
                                                    <strong>By the organiser's lot draw</strong>
                                                    <span style="display:block;color:#8E8E93;font-size:0.78rem;">You do not choose your own lot.</span>
                                                </span>
                                            </div>
                                        @endif
                                        <div class="d-flex justify-content-between py-2" style="font-size:0.85rem;">
                                            <span style="color:#8E8E93;">Confirmed On</span>
                                            <strong>{{ $poll->created_at->format('d M Y, h:i A') }}</strong>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if(isset($assignedPlot) && $assignedPlot)
                                {{-- The check-in code, so a participant can be checked in from
                                     their phone without printing anything — see User::icHash().
                                     The printed slip carries the same code once it exists. --}}
                                @php
                                    $icQr = auth()->user()->icQrDataUri(200);
                                    $icHash = auth()->user()->icHash();
                                @endphp
                                <div class="ap-card mb-3">
                                    <div class="ap-card-body-sm text-center">
                                        @if($icQr)
                                            <img src="{{ $icQr }}" alt="Your check-in code" loading="lazy"
                                                 style="width:150px;height:150px;image-rendering:pixelated;">
                                            <div style="font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
                                                        font-size:0.9rem;letter-spacing:2px;color:#1C1C1E;margin-top:6px;">
                                                {{ strtoupper(substr($icHash, 0, 8)) }}
                                            </div>
                                            <div style="font-size:0.78rem;color:#8E8E93;margin-top:4px;">
                                                Show this at the counter to check in.
                                            </div>
                                        @else
                                            {{-- Without an IC there is nothing to encode. Say what is
                                                 missing and where to fix it — a blank space here reads
                                                 as a broken page on the day it is needed. --}}
                                            <div style="width:150px;height:150px;margin:0 auto;border:2px dashed rgba(60,60,67,0.18);
                                                        border-radius:12px;display:flex;align-items:center;justify-content:center;">
                                                <i class="bi bi-qr-code" style="font-size:2.4rem;color:#C7C7CC;"></i>
                                            </div>
                                            <div style="font-size:0.85rem;color:#1C1C1E;margin-top:10px;font-weight:600;">
                                                No check-in code yet
                                            </div>
                                            <div style="font-size:0.78rem;color:#8E8E93;margin-top:2px;">
                                                Your IC number is not on file. Ask the office to add it, and the code appears here.
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- Print opens the slip in the browser's own viewer, which
                                     has a print button; share hands the file itself to the
                                     OS share sheet. --}}
                                <div class="d-flex justify-content-center gap-2 mb-3">
                                    <a href="{{ route('zvote.slip', $event) }}" target="_blank" rel="noopener"
                                       class="ap-btn ap-btn-secondary ap-btn-sm">
                                        <i class="bi bi-printer me-1"></i>Print Slip
                                    </a>
                                    <button type="button" class="ap-btn ap-btn-secondary ap-btn-sm"
                                            data-share-slip="{{ route('zvote.slip', $event) }}"
                                            data-slip-name="lot-{{ $assignedPlot->plot_code }}.pdf">
                                        <i class="bi bi-share me-1"></i>Share Slip
                                    </button>
                                </div>
                            @endif

                            <a href="{{ route('dashboard') }}" class="ap-btn ap-btn-outline ap-btn-sm">
                                <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
                            </a>
                        </div>

                    @else
                        {{-- Vote form --}}

                        {{-- Allocated category notice --}}
                        <div class="mb-3 p-3" style="background:rgba(0,122,255,0.06);border-radius:10px;border:1px solid rgba(0,122,255,0.15);">
                            <div class="d-flex align-items-start gap-2">
                                <i class="bi bi-info-circle mt-1" style="color:#007AFF;flex-shrink:0;"></i>
                                <div>
                                    <div style="font-weight:600;font-size:0.82rem;color:#007AFF;margin-bottom:4px;">Allocated Lot Category</div>
                                    @if($code === 'NOT SET' || !$code)
                                        <div style="font-weight:700;color:#FF3B30;font-size:1rem;">
                                            <i class="bi bi-exclamation-triangle me-1"></i>NOT SET
                                        </div>
                                        <p style="font-size:0.8rem;color:#FF3B30;margin:4px 0 0;">Company code has not been assigned. Please contact admin.</p>
                                    @else
                                        <div style="font-weight:700;color:#1C1C1E;font-size:1.05rem;">{{ $code }}</div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Worded to hold for both paths. showVote deliberately does not
                             tell this page whether the lot was reserved in advance or is
                             still to be drawn — "kept a surprise" — so saying "drawn" was
                             wrong for anyone holding a reserved lot. "Assigned" is true
                             either way, and the two limits below are what the participant
                             actually needs before ticking the box. --}}
                        <div class="mb-3 p-3" style="background:rgba(52,199,89,0.06);border-radius:10px;border:1px solid rgba(52,199,89,0.15);">
                            <p style="font-size:0.82rem;color:#28A745;margin:0 0 6px;">
                                <i class="bi bi-gift me-1"></i>
                                <strong>Lot Draw</strong> — Confirming assigns you one lot from
                                @if($code && $code !== 'NOT SET')
                                    category <strong>{{ $code }}</strong>
                                @else
                                    your craft category
                                @endif
                                and reveals it straight away.
                            </p>
                            <p style="font-size:0.78rem;color:#5C8F6B;margin:0;">
                                You do not choose which lot, and you can only confirm once.
                            </p>
                        </div>

                        <form action="{{ route('zvote.submit', $event) }}" method="POST">
                            @csrf

                            <div class="mb-3 p-3" style="border:1px solid rgba(60,60,67,0.15);border-radius:10px;">
                                <div class="form-check ps-4">
                                    <input class="form-check-input" type="checkbox" name="agreement" id="agree" value="1" required
                                        {{ $code === 'NOT SET' ? 'disabled' : '' }}>
                                    <label class="form-check-label fw-semibold" for="agree" style="font-size:0.88rem;">
                                        I agree to this lot allocation and confirm my participation
                                    </label>
                                </div>
                            </div>

                            <button type="submit" class="ap-btn ap-btn-primary w-100 text-center"
                                {{ $code === 'NOT SET' ? 'disabled' : '' }}>
                                <i class="bi bi-check-circle me-2"></i>Confirm Lot
                            </button>
                        </form>

                        @if($code === 'NOT SET')
                            <div class="mt-2 p-3" style="background:rgba(255,149,0,0.08);border-radius:10px;border:1px solid rgba(255,149,0,0.2);">
                                <p style="font-size:0.8rem;color:#C77800;margin:0;">
                                    <i class="bi bi-lock me-1"></i>
                                    <strong>Lot draw locked:</strong> Company code must be set by admin first.
                                </p>
                            </div>
                        @endif

                        <div class="mt-2 p-3" style="background:rgba(255,149,0,0.06);border-radius:10px;border:1px solid rgba(255,149,0,0.15);">
                            <p style="font-size:0.8rem;color:#C77800;margin:0;">
                                <i class="bi bi-clock me-1"></i>
                                <strong>Lot draw closes:</strong>
                                {{ $event->polling_deadline?->format('d M Y, h:i A') ?? 'Not set' }}
                                &middot; Your lot is final once confirmed.
                            </p>
                        </div>
                    @endif

                </div>
            </div>

        </div>
    </div>
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
