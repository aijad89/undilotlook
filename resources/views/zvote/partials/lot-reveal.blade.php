{{--
    The reveal shown once, on the hop straight from confirming.

    Theatre over a decided result. submitVote picked the lot inside a locked
    transaction before this page was rendered, and the code is already printed
    in the details card below — so if JavaScript never runs, or the browser
    prefers reduced motion, the participant loses the animation and nothing
    else. Neither style shows how many lots exist, which the product withholds
    on purpose: the board rolls through characters, and the envelope shows one
    card. There is nothing here to count.

    Expects: $lotCode, $style ('splitflap' | 'envelope')
--}}
@php
    $chars = str_split($lotCode);
@endphp

<div class="lot-reveal lot-reveal--{{ $style }}" data-lot-reveal data-lot-code="{{ $lotCode }}">

    @if($style === 'envelope')
        <div class="lr-envelope" aria-hidden="true">
            <div class="lr-env-flap"></div>
            <div class="lr-env-body">
                <div class="lr-env-seal"><i class="bi bi-award"></i></div>
            </div>
            <div class="lr-env-card"><span class="lr-env-code">{{ $lotCode }}</span></div>
        </div>
    @else
        <div class="lr-board" aria-hidden="true">
            @foreach($chars as $char)
                <span class="lr-flap" data-final="{{ $char }}">{{ $char === '-' ? '-' : '0' }}</span>
            @endforeach
        </div>
    @endif

    {{-- No result line here. The "Lot No." row of the details card sits right
         below, carries the same code in plain server-rendered text, and needs
         no JavaScript — so this block is decoration end to end. --}}
</div>

@once
@push('styles')
<style>
    .lot-reveal { text-align:center; padding:18px 0 22px; }

    /* ── Split-flap ─────────────────────────────────────── */
    .lr-board { display:inline-flex; gap:5px; }
    .lr-flap {
        display:inline-flex; align-items:center; justify-content:center;
        min-width:34px; height:46px; padding:0 6px;
        background:var(--ui-navy-900); color:#fff; border-radius:6px;
        font-size:1.35rem; font-weight:700; font-variant-numeric:tabular-nums;
        box-shadow:0 1px 3px rgba(0,0,0,0.18), 0 6px 18px rgba(0,0,0,0.10);
        /* The hairline is the split in the flap, not decoration. */
        background-image:linear-gradient(to bottom, transparent calc(50% - 1px), rgba(255,255,255,0.14) 50%, transparent calc(50% + 1px));
    }
    .lr-flap.is-rolling { animation:lr-roll 0.09s linear infinite; }
    @keyframes lr-roll {
        0%   { transform:translateY(-8%) scaleY(0.94); }
        100% { transform:translateY(8%)  scaleY(1.06); }
    }
    /* 0.34s here is the LAND constant in the script below — change both. */
    .lr-flap.is-set { animation:lr-land 0.34s cubic-bezier(0.16, 1, 0.3, 1); }
    @keyframes lr-land {
        0%   { transform:translateY(-42%) scaleY(0.7); }
        70%  { transform:translateY(3%)   scaleY(1.04); }
        100% { transform:none; }
    }

    /* ── Envelope ───────────────────────────────────────── */
    /* The flap rotates about its own top edge, so once open it occupies its
       full 66px height ABOVE the envelope. Without this headroom it lands on
       the line of text above and covers it. Split-flap needs none of it. */
    .lot-reveal--envelope { padding-top:78px; }
    .lr-envelope { position:relative; width:230px; height:132px; margin:0 auto; }
    .lr-env-body {
        position:absolute; inset:0; border-radius:10px;
        background:#FAFAF8; border:1px solid rgba(60,60,67,0.14);
        box-shadow:0 1px 3px rgba(0,0,0,0.05), 0 8px 24px rgba(0,0,0,0.07);
    }
    .lr-env-flap {
        position:absolute; left:0; right:0; top:0; height:66px; z-index:3;
        background:#F2F2F7; border:1px solid rgba(60,60,67,0.14);
        border-radius:10px 10px 0 0;
        clip-path:polygon(0 0, 100% 0, 50% 100%);
        transform-origin:top center; transition:transform 1.3s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .lr-env-seal {
        position:absolute; left:50%; top:52px; transform:translate(-50%,-50%);
        width:34px; height:34px; border-radius:50%; z-index:4;
        display:flex; align-items:center; justify-content:center;
        background:var(--ui-gold-tint); color:var(--ui-gold-text); font-size:1rem;
        transition:transform 0.5s ease, opacity 0.5s ease;
    }
    .lr-env-card {
        position:absolute; left:14px; right:14px; top:22px; z-index:2;
        padding:14px 0; border-radius:8px; background:#fff;
        border:1px solid rgba(60,60,67,0.12);
        box-shadow:0 1px 3px rgba(0,0,0,0.05);
        opacity:0; transform:translateY(10px);
        /* Ends 2.0s after .is-open lands — the second half of the 4s. */
        transition:opacity 0.9s ease 0.6s, transform 1.4s cubic-bezier(0.16, 1, 0.3, 1) 0.6s;
    }
    .lr-env-code { font-size:1.45rem; font-weight:700; color:var(--ui-gold-text); letter-spacing:0.02em; }

    .lot-reveal.is-open .lr-env-flap { transform:rotateX(-172deg); }
    .lot-reveal.is-open .lr-env-seal { transform:translate(-50%,-50%) scale(0.4); opacity:0; }
    .lot-reveal.is-open .lr-env-card { opacity:1; transform:translateY(-16px); }

    /* Reduced motion: land on the answer, skip the performance entirely. */
    @media (prefers-reduced-motion: reduce) {
        .lr-flap, .lr-env-flap, .lr-env-seal, .lr-env-card { animation:none !important; transition:none !important; }
        .lr-env-flap, .lr-env-seal { display:none; }
        .lr-env-card { opacity:1; transform:none; }
    }
</style>
@endpush

@push('scripts')
<script>
(function () {
    const root = document.querySelector('[data-lot-reveal]');
    if (!root) return;

    // Both styles run for the same 4s, so the wait is the same whichever an
    // event is set to. The split-flap stretches its stagger to fit, so a long
    // code and a short one finish together too.
    const TOTAL = 4000;
    const LAND  = 340;  // lr-land in the stylesheet above

    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const settle  = () => root.classList.add('is-open');

    if (reduced) { settle(); return; }

    // The "Lot No." row of the details card prints the same code in plain text,
    // which answers the reveal before it starts. Hide it for the duration and
    // fade it back as the reveal lands.
    //
    // Hidden from script and not from CSS, deliberately: a browser that never
    // runs this file leaves the row visible, and that row is the whole reason
    // a failed animation costs nothing.
    const spoiler = document.querySelector('strong[data-lot-code]');
    if (spoiler) {
        spoiler.style.opacity = '0';
        setTimeout(() => {
            spoiler.style.transition = 'opacity 0.4s ease';
            spoiler.style.opacity = '';
        }, TOTAL);
    }

    if (root.classList.contains('lot-reveal--envelope')) {
        // Sealed for the first half, opening through the second.
        setTimeout(settle, TOTAL / 2);
        return;
    }

    // Split-flap: each cell rolls, then lands left to right. The rolling
    // characters are drawn from the code itself — nothing here implies a pool
    // of lots to count.
    const pool  = ['0','1','2','3','4','5','6','7','8','9','A','B','C','D','E','F','G','H','J','K','L','M','N','P','R','S','T'];
    const flaps = [...root.querySelectorAll('.lr-flap')];

    // First cell lands a second in, last one lands with just enough left for
    // its own drop, and the rest are spread evenly between.
    const first = 1000;
    const last  = TOTAL - LAND;
    const landsAt = i => flaps.length < 2 ? last : first + (last - first) * i / (flaps.length - 1);

    flaps.forEach((flap, i) => {
        const final = flap.dataset.final;
        if (final === '-') return;

        flap.classList.add('is-rolling');
        const spin = setInterval(() => {
            flap.textContent = pool[Math.floor(Math.random() * pool.length)];
        }, 60);

        setTimeout(() => {
            clearInterval(spin);
            flap.classList.remove('is-rolling');
            flap.textContent = final;
            flap.classList.add('is-set');
        }, landsAt(i));
    });
})();
</script>
@endpush
@endonce
