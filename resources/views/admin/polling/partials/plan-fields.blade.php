{{--
    Locations, with their bidang allocations nested inside each one.

    The create wizard's second page and the edit form are the same form over the
    same columns, so they are the same file. They used to be two, and the two
    drifted: ten identical JavaScript functions and the whole nested markup,
    maintained twice, which is how the edit page ended up with a normaliser the
    create page never got.

    Expects:
      $event          — the Event being edited, or null while creating
      $groupedBidangs — from PollingController::groupedBidangs()

    A row here is a LOCATION — where the plots are and how many it holds. The
    booth size sits on the allocation instead, because one hall holds more than
    one size: RKP is allocated at both 3x6 and 3x3 on the same list, and a
    single size per location could not say so.

    plot_sizes is still keyed by (location, size) — that pair is what a plot
    carries, and a registration's booth size is matched against it — so
    savePlan() resolves the pairs this form names back into rows, reusing the
    ones that survive.

    An allocation belongs to one location, not to the event, so it is nested
    under its location rather than listed flat. The link travels in a hidden
    plot_size_index, which reindexPlotSizes() keeps in step as rows move.
--}}
@php
    $event = $event ?? null;

    // One shape for all three sources — old input after a bounce, what is on
    // file when editing, and a single starter row when neither applies — so the
    // row below is written once instead of once per case.
    // A location's `size` is the size of its plots no allocation claims. They
    // are the open pool, and a lot is drawn only at the registration's own booth
    // size — so a spare plot with no size can never be won by anyone. On file it
    // is read back off the (location, size) row holding more than its allocations.
    $allocatedOn = collect($event?->bidangAllocations ?? [])->groupBy('plot_size_id')->map->sum('plots_count');

    $locationsToRender = old('plot_sizes')
        ? collect(old('plot_sizes'))->map(fn ($s) => [
            'location' => $s['location'] ?? '',
            'quantity' => $s['quantity'] ?? 1,
            'size' => $s['size'] ?? '',
        ])->values()
        : ($event?->plotSizes
            ->groupBy(fn ($s) => (string) $s->location)
            ->map(fn ($rows, $loc) => [
                'location' => $loc,
                'quantity' => $rows->sum('quantity'),
                'size' => (string) $rows->first(fn ($r) => $r->quantity > ($allocatedOn[$r->id] ?? 0))?->size,
            ])
            ->values() ?? collect());

    if ($locationsToRender->isEmpty()) {
        $locationsToRender = collect([['location' => 'A', 'quantity' => 30, 'size' => '']]);
    }

    $locationKeys = $locationsToRender->map(fn ($l) => (string) $l['location'])->all();

    // Old input still wins over what is stored.
    $allocationsToRender = old('bidang_allocations');
    if ($allocationsToRender === null) {
        $allocationsToRender = [];
        foreach ($event?->bidangAllocations ?? [] as $alloc) {
            $index = array_search((string) $alloc->plotSize?->location, $locationKeys, true);
            if ($index === false) continue;   // its location was deleted
            $allocationsToRender[] = [
                'bidang_id'       => $alloc->bidang_id,
                'plot_size_index' => $index,
                'size'            => $alloc->plotSize?->size,
                'plots_count'     => $alloc->plots_count,
            ];
        }
    }

    // preserveKeys: the outer key IS the bidang_allocations[n] index. update()
    // matches an existing allocation by its own id, so the numbering only has
    // to stay unique across the form.
    $allocationsBySize = collect($allocationsToRender)->groupBy('plot_size_index', true);

    // The sizes this form offers: Settings › Dropdown Lists, plus any size an
    // allocation already carries. The size has to match the spelling on the
    // registrations in the draw for the reference card to pair the two up, so it is
    // a list rather than a free field — but a plan already laid out in a size
    // since dropped from Settings must not lose it on the next save.
    $sizeOptions = collect(\App\Support\PlotSizes::all())
        ->merge(collect($allocationsToRender)->pluck('size'))
        ->merge($locationsToRender->pluck('size'))
        ->map(fn ($s) => trim((string) $s))
        ->filter()
        ->unique()
        ->values();
@endphp

<div id="plotSizesContainer">
@foreach($locationsToRender as $psIndex => $loc)
    @php
        $allocsForSize = $allocationsBySize[$psIndex] ?? collect();
        $sizeQty       = (int) ($loc['quantity'] ?? 0);
        $sizeAllocated = $allocsForSize->sum(fn ($a) => (int) ($a['plots_count'] ?? 0));
        $sizeRemaining = $sizeQty - $sizeAllocated;
    @endphp
    <div class="plot-size-row mb-3" data-size-idx="{{ $psIndex }}" style="border:1px solid rgba(60,60,67,0.12);border-radius:12px;overflow:hidden;">
        <div class="p-3 px-4" style="background:rgba(0,0,0,0.02);">
            <div class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="mac-label">Location <span class="text-danger">*</span></label>
                    <input type="text" name="plot_sizes[{{ $psIndex }}][location]" class="mac-form-control plot-location" value="{{ $loc['location'] }}" placeholder="e.g., A" required>
                </div>
                <div class="col-md-3">
                    <label class="mac-label">Quantity <span class="text-danger">*</span></label>
                    <input type="number" name="plot_sizes[{{ $psIndex }}][quantity]" class="mac-form-control plot-quantity" value="{{ $loc['quantity'] }}" placeholder="e.g., 30" required min="1">
                </div>
                <div class="col-md-3">
                    <label class="mac-label" title="The plots no allocation below claims. They are the open pool, drawn by anyone whose code has run out — at this size.">Size of the rest</label>
                    <select name="plot_sizes[{{ $psIndex }}][size]" class="mac-select plot-leftover-size" required>
                        @foreach($sizeOptions as $option)
                            <option value="{{ $option }}" @selected($loc['size'] === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="button" class="mac-action-btn-danger mac-action-btn-field remove-size" aria-label="Remove this location" title="Remove location" @if($locationsToRender->count() <= 1) disabled @endif><i class="bi bi-trash" aria-hidden="true"></i></button>
                </div>
            </div>
        </div>
        <div class="bidang-sub-container p-3" style="border-top:1px solid rgba(60,60,67,0.08);background:rgba(0,113,227,0.015);">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="d-flex align-items-center gap-2">
                    <small class="fw-semibold" style="color:#374151;">Bidang Allocations</small>
                    <span class="size-remaining-badge mac-badge {{ $sizeRemaining === 0 ? 'mac-badge-green' : ($sizeRemaining < 0 ? 'mac-badge-red' : 'mac-badge-gray') }}">
                        {{ $sizeRemaining === 0 ? 'All allocated ✓' : ($sizeRemaining < 0 ? 'Over by '.abs($sizeRemaining) : $sizeRemaining.' remaining') }}
                    </span>
                </div>
                <button type="button" class="add-bidang-btn mac-btn-secondary" style="font-size:0.78rem;padding:4px 10px;">
                    <i class="bi bi-plus"></i> Add Bidang
                </button>
            </div>
            <div class="bidang-rows-container">
                @foreach($allocsForSize as $baIdx => $alloc)
                    @php
                        // The Category select is not submitted, so which one to
                        // preselect is read back off the code that is saved.
                        $cat = $groupedBidangs->search(
                            fn ($group) => $group->contains('id', (int) ($alloc['bidang_id'] ?? 0))
                        ) ?: null;
                    @endphp
                    <div class="bidang-allocation-row mb-2 p-2" style="background:rgba(255,255,255,0.7);border-radius:8px;border:1px solid rgba(60,60,67,0.1);">
                        <input type="hidden" name="bidang_allocations[{{ $baIdx }}][plot_size_index]" value="{{ $psIndex }}" class="bidang-size-index">
                        {{-- The allocation id used to be posted here, for savePlan()'s
                             isset($alloc['id']) arm. That arm never ran — no rule names the key,
                             so validate() strips it — and it was deleted together with this field
                             on 2026-08-27 (finding 26). Reviving either without the other opens a
                             cross-event write; the reason is in savePlan(). --}}
                        <div class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <label class="mac-label">Category <span class="text-danger">*</span></label>
                                {{-- Not submitted: it only narrows the Code list beside it. --}}
                                <select class="mac-select main-category-select" required>
                                    <option value="">-- Category --</option>
                                    @foreach($groupedBidangs->keys() as $category)
                                        <option value="{{ $category }}" {{ $cat === $category ? 'selected' : '' }}>{{ $category }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="mac-label">Code <span class="text-danger">*</span></label>
                                <select name="bidang_allocations[{{ $baIdx }}][bidang_id]" class="mac-select bidang-select" required>
                                    <option value="">-- Code --</option>
                                    @if($cat)
                                        @foreach($groupedBidangs[$cat] as $bidang)
                                            <option value="{{ $bidang['id'] }}" {{ ($alloc['bidang_id'] ?? '') == $bidang['id'] ? 'selected' : '' }}>
                                                [{{ $bidang['code'] }}] {{ $bidang['pecahan_bidang'] ?? $bidang['name'] }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="mac-label">Size <span class="text-danger">*</span></label>
                                <select name="bidang_allocations[{{ $baIdx }}][size]" class="mac-select bidang-size" required>
                                    @foreach($sizeOptions as $option)
                                        <option value="{{ $option }}" {{ ($alloc['size'] ?? '') === $option ? 'selected' : '' }}>{{ $option }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="mac-label">Plots <span class="text-danger">*</span> <span class="mac-muted" style="font-weight:400;">auto</span></label>
                                <input type="number" name="bidang_allocations[{{ $baIdx }}][plots_count]" class="mac-form-control plots-count" value="{{ $alloc['plots_count'] ?? 1 }}" required min="1" title="Filled from the companies in the draw holding this code — override it if the plan says otherwise.">
                            </div>
                            <div class="col-md-1 d-flex align-items-end">
                                <button type="button" class="mac-action-btn-danger mac-action-btn-field remove-bidang" aria-label="Remove this bidang allocation" title="Remove bidang allocation"><i class="bi bi-trash" aria-hidden="true"></i></button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endforeach
</div>{{-- /plotSizesContainer --}}

<div class="alert alert-info mt-3">
    <i class="bi bi-info-circle"></i>
    <strong>Total plots:</strong> <span id="calculatedTotal">0</span> &nbsp;|&nbsp;
    <strong>Bidang allocated:</strong> <span id="allocationTotal">0</span> / <span id="allocationTotalExpected">0</span>
    <span id="allocationStatus"></span>
</div>
