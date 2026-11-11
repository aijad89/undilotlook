{{--
    How a request paid, and the files that prove it: the receipt, or a waiver's
    supporting documents. The certificates and product photos sit in the same
    list but are the company's, not the payment's, so they are left out. An
    image previews on hover or focus; a PDF opens in a new tab.

    Expects $jr (EventJoinRequest, or null for a row the office added).
--}}
@if($jr)
    {{ \App\Models\EventJoinRequest::PAYMENT_METHODS[$jr->payment_method] ?? '—' }}
    @if($jr->waiver_category)
        <div class="mac-muted">{{ \App\Models\EventJoinRequest::WAIVER_CATEGORIES[$jr->waiver_category] ?? $jr->waiver_category }}</div>
    @endif
    @foreach($jr->documents ?? [] as $i => $doc)
        @continue(! str_starts_with($doc['path'] ?? '', 'join-requests/'))
        @php
            $url = route('join-requests.document', [$jr, $i]);
            $isImage = in_array(strtolower(pathinfo($doc['path'], PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png'], true);
        @endphp
        <div class="mac-proof">
            <a href="{{ $url }}" target="_blank" rel="noopener">
                <i class="bi {{ $isImage ? 'bi-file-earmark-image' : 'bi-file-earmark-pdf' }} me-1"></i>{{ $doc['name'] }}
            </a>
            @if($isImage)
                <span class="mac-proof-pop" aria-hidden="true"><img src="{{ $url }}" alt="" loading="lazy"></span>
            @endif
        </div>
    @endforeach
@else
    <span class="mac-muted">Added by the office</span>
@endif

@once
@push('styles')
<style>
    .mac-proof { position: relative; }
    /* Fixed, not absolute: the table scrolls in a box that would clip it. */
    .mac-proof-pop { display: none; position: fixed; z-index: 1060; padding: 4px; background: var(--ui-surface); border: 1px solid var(--ui-border); border-radius: var(--ui-r); box-shadow: var(--ui-shadow-pop); }
    .mac-proof-pop img { display: block; max-width: 320px; max-height: 320px; }
    .mac-proof:hover .mac-proof-pop, .mac-proof:focus-within .mac-proof-pop { display: block; }
</style>
@endpush
@push('scripts')
<script>
// Place the preview under its link, against the window.
document.addEventListener('DOMContentLoaded', () => document.querySelectorAll('.mac-proof').forEach(el => {
    const pop = el.querySelector('.mac-proof-pop');
    if (!pop) return;
    const place = () => {
        const r = el.getBoundingClientRect();
        pop.style.left = Math.max(8, Math.min(r.left, innerWidth - 340)) + 'px';
        pop.style.top = (r.bottom + 336 < innerHeight ? r.bottom + 4 : Math.max(8, r.top - 336)) + 'px';
    };
    el.addEventListener('mouseenter', place);
    el.addEventListener('focusin', place);
}));
</script>
@endpush
@endonce
