@extends('layouts.admin')

@section('title', 'Plot Codes — '.$event->title)

@section('content')
@include('partials.mac-styles')

<style>
/* A plot is a square. Its fill is the code painted on it, and an unpainted
   square is visibly unpainted — that is the whole reason this page exists. */
.plot-grid { display: flex; flex-wrap: wrap; gap: 4px; }
.plot-cell {
    width: 60px; height: 40px; padding: 0;
    border: 1px solid var(--ui-border); border-radius: 6px;
    background: var(--ui-surface); color: var(--ui-muted);
    font-size: 0.75rem; font-weight: 700;
    cursor: pointer; user-select: none;
    transition: transform 0.06s;
}
.plot-cell:hover, .plot-cell:focus-visible { transform: scale(1.08); border-color: var(--ui-blue); }
.plot-cell.painted { color: #fff; border-color: var(--ui-shade); }
/* Locked, not faded: fading the fill drops the white code below a readable
   contrast. A hatch says "cannot touch" and keeps the code legible. */
.plot-cell:disabled {
    cursor: not-allowed; transform: none;
    background-image: repeating-linear-gradient(135deg, transparent 0 6px, rgba(255, 255, 255, 0.22) 6px 8px);
}
.plot-cell:not(.painted):disabled { background-image: repeating-linear-gradient(135deg, transparent 0 6px, var(--ui-sep) 6px 8px); }

.code-chip {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 6px 10px; border-radius: 8px; cursor: pointer;
    border: 2px solid transparent; font-size: 0.8125rem; font-weight: 600;
    background: var(--ui-surface-2); color: var(--ui-text);
}
.code-chip[aria-pressed="true"] { border-color: var(--ui-text); }
.code-chip .tally { font-weight: 400; font-size: 0.75rem; }
.code-chip.met   .tally { color: var(--ui-ok-text); }
.code-chip.short .tally { color: var(--ui-bad-text); }
.code-chip.over  .tally { color: var(--ui-warn-text); }
.swatch { width: 12px; height: 12px; border-radius: 4px; display: inline-block; }
</style>

<div class="mac-page">
    @include('partials.admin-alerts')

    <div class="mac-breadcrumb mb-2">
        <a href="{{ route('admin.polling.index') }}">Lot Draw</a> <span class="sep" aria-hidden="true">›</span> <a href="{{ route('admin.polling.show', $event) }}">{{ $event->title }}</a> <span class="sep" aria-hidden="true">›</span> Plot Codes
    </div>

    <div class="mac-header mb-4">
        <h2>Plot Codes</h2>
        <p class="mac-muted mb-0">Paint craft codes straight onto the plots. Saving renumbers them to match.</p>
    </div>

    @if($frozen)
        <div class="alert alert-danger">
            <i class="bi bi-lock-fill"></i>
            <strong>Lots are already held in this draw.</strong> The codes on these plots are locked — moving
            one now would move a company into a category nobody put them in.
        </div>
    @endif

    {{-- The palette. A chip reads its own tally, so a code with nobody's square
         on it says 0 / N in red and cannot be mistaken for covered. --}}
    <div class="mac-card mb-4">
        <div class="mac-card-header">
            <div class="mac-card-title"><i class="bi bi-palette"></i> Craft codes in this draw</div>
        </div>
        <div class="mac-card-padded">
            @if($demand->isEmpty())
                <span class="mac-muted">Nobody in the draw carries a craft code yet, so there is nothing to paint.</span>
            @else
                <div class="d-flex flex-wrap gap-2 mb-3" role="group" aria-label="Brush">
                    @foreach($demand as $d)
                        <button type="button" class="code-chip" aria-pressed="false"
                                data-bidang="{{ $d['bidang']->id }}" data-code="{{ $d['bidang']->code }}" data-needed="{{ $d['needed'] }}"
                                title="{{ $d['bidang']->name }} — {{ $d['needed'] }} in the draw hold {{ $d['bidang']->code }}">
                            <span class="swatch"></span>
                            {{ $d['bidang']->code }}
                            <span class="tally">0 / {{ $d['needed'] }}</span>
                        </button>
                    @endforeach
                    <button type="button" class="code-chip" aria-pressed="true" data-bidang="" data-code=""
                            title="Leave the plot uncoded — anyone still unplaced can draw it">
                        <span class="swatch" style="background:var(--ui-surface);border:1px solid var(--ui-border);"></span>
                        Uncoded
                        <span class="tally">0</span>
                    </button>
                </div>
                <small class="mac-muted">
                    Pick a code, then click plots to put it on them — or drag across several.
                    @if($uncoded > 0)
                        <br><span class="text-danger">{{ $uncoded }} in the draw carry no known code</span> —
                        they can only draw from the uncoded plots, and polling will not open while any has none.
                    @endif
                </small>
            @endif
        </div>
    </div>

    <form action="{{ route('admin.polling.paint.update', $event) }}" method="POST" id="paintForm">
        @csrf
        @method('PUT')

        @foreach($event->plotSizes as $size)
            @php $sizePlots = $plots[$size->id] ?? collect(); @endphp
            <div class="mac-card mb-4">
                <div class="mac-card-header d-flex justify-content-between align-items-center">
                    <div class="mac-card-title">
                        <i class="bi bi-grid-3x3"></i>
                        {{ $size->location ?: 'No location' }} — {{ $size->size ?: 'no size' }}
                        <span class="mac-badge mac-badge-gray ms-2">{{ $sizePlots->count() }} plots</span>
                    </div>
                    <small class="mac-muted" data-size-summary="{{ $size->id }}"></small>
                </div>
                <div class="mac-card-padded">
                    @if($sizePlots->isEmpty())
                        <span class="mac-muted">No plots built for this size.</span>
                    @else
                        <div class="plot-grid" data-size="{{ $size->id }}">
                            @foreach($sizePlots as $plot)
                                <button type="button" class="plot-cell"
                                        data-plot="{{ $plot->id }}" data-bidang="{{ $plot->bidang_id }}"
                                        aria-label="{{ $plot->plot_code }}{{ $plot->is_allocated ? ', held' : '' }}"
                                        title="{{ $plot->plot_code }}{{ $plot->is_allocated ? ' — held' : '' }}"
                                        @disabled($frozen || $plot->is_allocated)>
                                    {{ $plot->bidang?->code ?: '—' }}
                                </button>
                                <input type="hidden" name="paint[{{ $plot->id }}]" value="{{ $plot->bidang_id }}">
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @endforeach

        <div class="d-flex justify-content-between mt-4">
            <a href="{{ route('admin.polling.show', $event) }}" class="mac-btn-secondary">Cancel</a>
            <button type="submit" class="mac-btn-primary" @disabled($frozen)>
                <i class="bi bi-save"></i> Save Codes
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const chips = [...document.querySelectorAll('.code-chip')];
    const cells = [...document.querySelectorAll('.plot-cell')];

    // One colour per code, from its place in the palette. Only has to be
    // distinct on this page, and nothing else reads it.
    const coded = chips.filter(c => c.dataset.bidang);
    const colours = {};
    coded.forEach((chip, i) => {
        // 30% lightness: white code text clears 4.5:1 on every hue, yellow included.
        colours[chip.dataset.bidang] = `hsl(${Math.round(215 + i * 360 / Math.max(1, coded.length)) % 360} 55% 30%)`;
        chip.querySelector('.swatch').style.background = colours[chip.dataset.bidang];
    });

    let brush = chips.find(c => c.getAttribute('aria-pressed') === 'true');

    chips.forEach(chip => chip.addEventListener('click', () => {
        chips.forEach(c => c.setAttribute('aria-pressed', 'false'));
        chip.setAttribute('aria-pressed', 'true');
        brush = chip;
    }));

    function show(cell) {
        const id = cell.dataset.bidang;
        cell.style.background = id && colours[id] ? colours[id] : '';
        cell.classList.toggle('painted', !!(id && colours[id]));
    }

    function paint(cell) {
        if (cell.disabled || !brush) return;

        cell.dataset.bidang = brush.dataset.bidang;
        cell.textContent = brush.dataset.code || '—';
        // The hidden input is what posts; the square is only how it looks.
        document.querySelector(`input[name="paint[${cell.dataset.plot}]"]`).value = brush.dataset.bidang;
        show(cell);
        retally();
    }

    // Every chip against what is actually on the grid: a code at 0 / 7 is seven
    // people who will draw from the uncoded pool instead of their own category.
    function retally() {
        chips.forEach(chip => {
            const id = chip.dataset.bidang;
            const tally = chip.querySelector('.tally');
            const have = cells.filter(c => (c.dataset.bidang || '') === id).length;

            if (!id) {
                tally.textContent = have + ' plots';
                return;
            }

            const need = parseInt(chip.dataset.needed) || 0;
            tally.textContent = `${have} / ${need}`;
            chip.classList.toggle('met', have === need);
            chip.classList.toggle('short', have < need);
            chip.classList.toggle('over', have > need);
        });

        document.querySelectorAll('[data-size-summary]').forEach(el => {
            const own = cells.filter(c => c.closest('.plot-grid')?.dataset.size === el.dataset.sizeSummary);
            const bare = own.filter(c => !c.dataset.bidang).length;
            el.textContent = own.length === 0 ? '' : (bare === 0 ? 'all coded' : `${bare} of ${own.length} still uncoded`);
        });
    }

    // Click (or Enter/Space) paints one; dragging with the mouse paints a run.
    let dragging = false;
    cells.forEach(cell => {
        cell.addEventListener('click', () => paint(cell));
        cell.addEventListener('mousedown', e => { dragging = true; e.preventDefault(); paint(cell); });
        cell.addEventListener('mouseenter', () => { if (dragging) paint(cell); });
    });
    document.addEventListener('mouseup', () => { dragging = false; });

    cells.forEach(show);
    retally();

    document.getElementById('paintForm').addEventListener('submit', function (e) {
        const short = coded.filter(c => c.classList.contains('short'));
        if (short.length === 0) return;

        const names = short.map(c => `${c.dataset.code} ${c.querySelector('.tally').textContent}`).join(', ');
        if (!confirm(`These codes have fewer plots than people holding them:\n\n${names}\n\nThose people will draw from the uncoded plots instead of their own category. Save anyway?`)) {
            e.preventDefault();
        }
    });
});
</script>
@endpush
@endsection
