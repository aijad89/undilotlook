{{--
    The script half of plan-fields. Same reason it is one file: these ten
    functions were maintained twice and had already begun to differ.

    Expects the markup from plan-fields to be on the page, plus:
      $groupedBidangs — the Category → Code map
      $drawCount  — for the reference card (0 if absent)
--}}
<script>
document.addEventListener('DOMContentLoaded', function() {

    @if($errors->any())
    const form = document.getElementById('pollingForm');
    if (form) {
        form.scrollIntoView({ behavior: 'smooth', block: 'start' });
        setTimeout(() => {
            const firstError = document.querySelector('.text-danger');
            if (firstError) firstError.closest('.mb-3')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 300);
    }
    @endif

    // Counted off the rendered form rather than passed in: plan-fields defines
    // its own scope, and the pushed script stack renders outside it. The DOM is
    // the same answer and cannot fall out of step with the page it describes.
    let sizeCounter = document.querySelectorAll('.plot-size-row').length;

    // The next free bidang_allocations[n]. NOT the row count — a validation
    // bounce can hand back sparse indices (0, 3, 7 after two removals), and
    // starting at 3 would overwrite the row already sitting at 3.
    let bidangCounter = 0;
    document.querySelectorAll('[name^="bidang_allocations["]').forEach(el => {
        const m = el.name.match(/^bidang_allocations\[(\d+)\]/);
        if (m) bidangCounter = Math.max(bidangCounter, parseInt(m[1]) + 1);
    });

    const groupedBidangs = @json($groupedBidangs);

    // Settings › Dropdown Lists › Plot Sizes. A new row offers the current list
    // only — a size a saved allocation still carries is rendered by plan-fields
    // on that row, and is not something to start a fresh one on.
    const plotSizes = @json(\App\Support\PlotSizes::all());

    function getSizesHtml() {
        return plotSizes.map(s => `<option value="${s}">${s}</option>`).join('');
    }

    // ── Category options HTML ──────────────────────────────
    function getCategoriesHtml() {
        let html = '';
        if (groupedBidangs) {
            Object.keys(groupedBidangs).forEach(cat => {
                html += `<option value="${cat}">${cat}</option>`;
            });
        }
        return html;
    }

    // ── Populate code dropdown from category select ────────
    function updateCodeDropdown(categorySelect) {
        const row = categorySelect.closest('.bidang-allocation-row');
        if (!row) return;
        const codeSelect = row.querySelector('.bidang-select');
        if (!codeSelect) return;
        // Rebuilding the list drops whatever was selected. After a validation
        // bounce that selection is the code the admin already picked, restored
        // by the server — and when the category really changed the old code is
        // not in the new list, so the assignment quietly leaves it empty.
        const previous = codeSelect.value;
        const category = categorySelect.value;
        codeSelect.innerHTML = '<option value="">-- Code --</option>';
        if (!category || !groupedBidangs[category]) return;
        groupedBidangs[category].forEach(bidang => {
            const opt = document.createElement('option');
            opt.value = bidang.id;
            opt.textContent = `[${bidang.code}] ${bidang.pecahan_bidang || bidang.name || ''}`;
            codeSelect.appendChild(opt);
        });
        if (previous) codeSelect.value = previous;
    }

    // ── Plots, filled from the code that was just picked ───
    //
    // The number an allocation wants is not a guess: it is how many companies
    // in the draw hold that code, which the reference card above already counts on
    // to a chip. Typing it again by reading the chip was the step everyone
    // skipped, and a code left at 1 plot reads as allocated while its other
    // holders draw outside their category.
    //
    // Minus what the other rows already give the same code — a code split
    // across two halls asks for the rest, not the whole demand twice. Still a
    // plain input: the plan can want more or fewer than the demand, and typing
    // over it is the override.
    function autofillPlots(row) {
        const id    = row?.querySelector('.bidang-select')?.value;
        const size  = rowSize(row);
        const input = row?.querySelector('.plots-count');
        if (!input || !id) return;

        // The chip for this exact code AND size, since that is what the
        // draw list states. Falling back to the any-size chip covers the
        // moment before a size is typed, and the registrations that asked for
        // no particular one.
        const chip = document.querySelector(`.coverage-chip[data-bidang="${id}"][data-size="${size}"]`)
            || document.querySelector(`.coverage-chip[data-bidang="${id}"][data-size=""]`);
        if (!chip) return;   // nobody in the draw is asking for this code at this size

        let elsewhere = 0;
        document.querySelectorAll('.bidang-allocation-row').forEach(other => {
            if (other === row) return;
            if (other.querySelector('.bidang-select')?.value === id && rowSize(other) === size) {
                elsewhere += parseInt(other.querySelector('.plots-count')?.value) || 0;
            }
        });

        input.value = Math.max((parseInt(chip.dataset.needed) || 0) - elsewhere, 1);
    }

    function rowSize(row) {
        return (row?.querySelector('.bidang-size')?.value || '').trim();
    }

    // ── Remaining badge for one plot-size-row ──────────────
    function updateSizeRemainingBadge(plotSizeRow) {
        const badge = plotSizeRow.querySelector('.size-remaining-badge');
        if (!badge) return;
        const qty = parseInt(plotSizeRow.querySelector('.plot-quantity')?.value) || 0;
        let allocated = 0;
        plotSizeRow.querySelectorAll('.plots-count').forEach(inp => { allocated += parseInt(inp.value) || 0; });
        const remaining = qty - allocated;
        if (remaining > 0) {
            badge.textContent = `${remaining} remaining`;
            badge.className = 'size-remaining-badge mac-badge mac-badge-gray';
        } else if (remaining === 0) {
            badge.textContent = 'All allocated ✓';
            badge.className = 'size-remaining-badge mac-badge mac-badge-green';
        } else {
            badge.textContent = `Over by ${Math.abs(remaining)}`;
            badge.className = 'size-remaining-badge mac-badge mac-badge-red';
        }
    }

    // ── Sync total_plots hidden field + global summary ─────
    function calculateTotals() {
        let total = 0;
        document.querySelectorAll('.plot-quantity').forEach(inp => { total += parseInt(inp.value) || 0; });
        document.getElementById('calculatedTotal').textContent = total;
        const display = document.getElementById('totalPlotsDisplay');
        if (display) display.textContent = total;
        document.getElementById('totalPlotsInput').value = total;
        updatePlanReference(total);
        document.querySelectorAll('.plot-size-row').forEach(row => updateSizeRemainingBadge(row));
        calculateAllocations();
    }

    // THE REFERENCE CARD IS ADVICE, NOT A READOUT, AND ITS WORDING MATTERS MORE
    // THAN ITS ARITHMETIC. It is read while quantities are typed, which is when
    // the ratio decides whether everyone gets one 3x3 or some get a 3x6.
    //
    // Nobody holds two lots: event_registrations.plot_id is one column, and a
    // company is in one event's draw once. A surplus plot is never drawn — it
    // stays unassigned — so the surplus branch says so, and never "N each".
    const drawCount = {{ (int) ($drawCount ?? 0) }};

    function updatePlanReference(total) {
        const plots = document.getElementById('planRefPlots');
        const ratio = document.getElementById('planRefRatio');
        if (!plots || !ratio) return;

        plots.textContent = total;

        if (drawCount === 0) {
            ratio.textContent = 'Nobody in the draw yet — nothing to plan against.';
            return;
        }

        if (total < drawCount) {
            const short = drawCount - total;
            ratio.innerHTML = `<strong style="color:var(--ui-bad-text);">${short} short</strong> — ${short} companies would have no lot.`;
        } else if (total === drawCount) {
            ratio.innerHTML = '<strong style="color:var(--ui-ok-text);">Exactly one each.</strong>';
        } else {
            const spare = total - drawCount;
            ratio.innerHTML = `<strong style="color:var(--ui-ok-text);">One each</strong>, and `
                + `<strong>${spare} plot${spare === 1 ? '' : 's'} left over</strong> — `
                + `nobody holds two, so ${spare === 1 ? 'it stays' : 'they stay'} unassigned. `
                + `Trim the plan, or move the spare capacity to a bigger size.`;
        }
    }

    // The coverage chips, recomputed from the form.
    //
    // The card renders from the plots on file, which is the truth on the edit
    // form and zero on the create one — the plan there is still being typed. So
    // the supply half is read back off the allocation rows every time they
    // change, and a code that has just been given plots turns green while the
    // planner is still looking at it.
    // "bidang id|size" => plots typed against it, across every location.
    // byCode is the same total ignoring size, for the chips of registrations
    // that asked for no particular one — they draw from all of them.
    function currentSupply() {
        const supply = {};
        const byCode = {};

        document.querySelectorAll('.bidang-allocation-row').forEach(row => {
            const id = row.querySelector('.bidang-select')?.value;
            const n = parseInt(row.querySelector('.plots-count')?.value) || 0;
            if (!id) return;
            const key = id + '|' + rowSize(row);
            supply[key] = (supply[key] || 0) + n;
            byCode[id] = (byCode[id] || 0) + n;
        });

        return { supply, byCode };
    }

    // bidang id => the sizes it is still short of. What is left to allocate,
    // read off the same chips and the same form the tally is.
    function stillShort() {
        const { supply, byCode } = currentSupply();
        const out = {};

        document.querySelectorAll('.coverage-chip[data-bidang]').forEach(chip => {
            const id = chip.dataset.bidang;
            if (!id) return;
            const size = chip.dataset.size || '';
            const need = parseInt(chip.dataset.needed) || 0;
            const have = size ? (supply[id + '|' + size] || 0) : (byCode[id] || 0);

            if (have < need) (out[id] = out[id] || []).push(size);
        });

        return out;
    }

    function retallyCoverage() {
        const chips = document.querySelectorAll('.coverage-chip[data-bidang]');
        if (chips.length === 0) return;

        const { supply, byCode } = currentSupply();

        let short = 0;

        chips.forEach(chip => {
            const id = chip.dataset.bidang;
            const size = chip.dataset.size || '';
            const need = parseInt(chip.dataset.needed) || 0;
            if (!id) return;

            const have = size ? (supply[id + '|' + size] || 0) : (byCode[id] || 0);
            chip.querySelector('.chip-tally').textContent = `${need} / ${have}`;

            chip.classList.remove('mac-badge-red', 'mac-badge-orange', 'mac-badge-green');
            chip.classList.add(have === 0 ? 'mac-badge-red' : (have < need ? 'mac-badge-orange' : 'mac-badge-green'));

            if (have < need) short += (need - have);
        });

        const summary = document.getElementById('coverageSummary');
        if (summary) {
            summary.textContent = short === 0
                ? 'every code has plots'
                : `${short} companies would draw outside their own category`;
            summary.style.color = short === 0 ? 'var(--ui-ok-text)' : 'var(--ui-bad-text)';
        }
    }

    // ── The code list, narrowed to what is left to do ──────
    //
    // A code whose every demand is met has nothing more to allocate, so it stops
    // being offered — picking TBPS again after its 36 3x3 are covered can only
    // build plots nobody asked for. It stays while ANY of its sizes is short:
    // TBPS covered at 3x3 and short at 3x6 is still work.
    //
    // Two codes are never hidden. The row's own selection, or it would clear
    // itself the moment it was satisfied; and a code with no chip at all, which
    // nobody in the draw is asking for — that one was never on the work list, and
    // hiding it would stop a planner building spare capacity on purpose.
    // The bidang ids some companies in the draw is actually asking for — every code
    // with a chip. A code with none was never on the work list.
    function askedFor() {
        return new Set(
            [...document.querySelectorAll('.coverage-chip[data-bidang]')]
                .map(c => c.dataset.bidang)
                .filter(Boolean)
        );
    }

    function refreshCodeChoices() {
        const short = stillShort();
        const asked = askedFor();

        document.querySelectorAll('.bidang-select').forEach(select => {
            const chosen = select.value;

            select.querySelectorAll('option').forEach(opt => {
                if (!opt.value) return;   // the "-- Code --" placeholder
                opt.hidden = opt.value !== chosen && asked.has(opt.value) && !short[opt.value];
            });
        });
    }

    // The same rule one level up: a bidang whose every code is covered has
    // nothing left under it, so it stops being offered.
    //
    // Not only tidiness. Without this the planner can pick TEKSTIL in the second
    // hall and open a Code list with nothing in it — a dead end the narrowing
    // above created. Note the count is across the WHOLE plan, not per location:
    // the demand is the event's, so TEKSTIL covered in hall A is done in hall B
    // and in every later row of hall A too.
    //
    // Same two exceptions as the codes: the row's own selection stays, and a
    // bidang whose codes nobody in the draw is asking for is never hidden — it was
    // never on the work list, and spare capacity is a real thing to plan.
    function refreshCategoryChoices() {
        const short = stillShort();
        const asked = askedFor();

        const done = {};
        Object.keys(groupedBidangs || {}).forEach(category => {
            const onTheList = groupedBidangs[category]
                .map(b => String(b.id))
                .filter(id => asked.has(id));

            done[category] = onTheList.length > 0 && onTheList.every(id => !short[id]);
        });

        document.querySelectorAll('.main-category-select').forEach(select => {
            const chosen = select.value;

            select.querySelectorAll('option').forEach(opt => {
                if (!opt.value) return;   // the "-- Category --" placeholder
                opt.hidden = opt.value !== chosen && done[opt.value];
            });
        });
    }

    // Point the size at what this code still needs. Picking TBPS when only its
    // 3x6 is short should land on 3x6 — the code and the size are one demand,
    // and leaving the size on a covered 3x3 makes the row ask for nothing.
    function pickShortSize(row) {
        const id      = row?.querySelector('.bidang-select')?.value;
        const sizeSel = row?.querySelector('.bidang-size');
        if (!id || !sizeSel) return;

        const wanted = stillShort()[id] || [];
        if (wanted.length === 0 || wanted.includes(rowSize(row))) return;

        const match = [...sizeSel.options].find(o => wanted.includes(o.value));
        if (match) sizeSel.value = match.value;
    }

    function calculateAllocations() {
        retallyCoverage();
        refreshCodeChoices();
        refreshCategoryChoices();

        let totalAllocated = 0;
        document.querySelectorAll('.plots-count').forEach(inp => { totalAllocated += parseInt(inp.value) || 0; });
        const totalPlots = parseInt(document.getElementById('totalPlotsInput').value) || 0;
        document.getElementById('allocationTotal').textContent = totalAllocated;
        const expEl = document.getElementById('allocationTotalExpected');
        if (expEl) expEl.textContent = totalPlots;
        const statusEl = document.getElementById('allocationStatus');
        if (totalPlots === 0) {
            statusEl.innerHTML = '';
        } else if (totalAllocated === totalPlots) {
            statusEl.innerHTML = '<span class="text-success ms-2"><i class="bi bi-check-circle"></i> All plots covered ✓</span>';
        } else if (totalAllocated > totalPlots) {
            statusEl.innerHTML = `<span class="text-danger ms-2"><i class="bi bi-exclamation-triangle"></i> Over by ${totalAllocated - totalPlots}</span>`;
        } else {
            statusEl.innerHTML = `<span class="ms-2" style="color:var(--ui-warn-text);"><i class="bi bi-exclamation-triangle"></i> ${totalPlots - totalAllocated} not yet assigned</span>`;
        }
    }

    // ── Re-index plot size rows after add/remove ───────────
    function reindexPlotSizes() {
        document.querySelectorAll('.plot-size-row').forEach((row, i) => {
            row.dataset.sizeIdx = i;
            row.querySelectorAll('[name^="plot_sizes["]').forEach(el => {
                el.name = el.name.replace(/^plot_sizes\[\d+\]/, `plot_sizes[${i}]`);
            });
            row.querySelectorAll('.bidang-size-index').forEach(el => { el.value = i; });
        });
        document.querySelectorAll('.plot-size-row').forEach(row => {
            const btn = row.querySelector('.remove-size');
            if (btn) btn.disabled = document.querySelectorAll('.plot-size-row').length <= 1;
        });
    }

    // ── Add a bidang allocation row to a plot-size-row ─────
    function addBidangRowTo(plotSizeRow) {
        const sizeIdx = parseInt(plotSizeRow.dataset.sizeIdx) || 0;
        const n = bidangCounter++;
        const html = `
            <div class="bidang-allocation-row mb-2 p-2" style="background:var(--ui-surface);border-radius:8px;border:1px solid var(--ui-border);">
                <input type="hidden" name="bidang_allocations[${n}][plot_size_index]" value="${sizeIdx}" class="bidang-size-index">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="mac-label">Category <span class="text-danger">*</span></label>
                        <select class="mac-select main-category-select" required>
                            <option value="">-- Category --</option>
                            ${getCategoriesHtml()}
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="mac-label">Code <span class="text-danger">*</span></label>
                        <select name="bidang_allocations[${n}][bidang_id]" class="mac-select bidang-select" required>
                            <option value="">-- Code --</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="mac-label">Size <span class="text-danger">*</span></label>
                        <select name="bidang_allocations[${n}][size]" class="mac-select bidang-size" required>
                            ${getSizesHtml()}
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="mac-label">Plots <span class="text-danger">*</span> <span class="mac-muted" style="font-weight:400;">auto</span></label>
                        <input type="number" name="bidang_allocations[${n}][plots_count]"
                            class="mac-form-control plots-count" required min="1" value="1"
                            title="Filled from the companies in the draw holding this code — override it if the plan says otherwise.">
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="button" class="mac-action-btn-danger mac-action-btn-field remove-bidang" aria-label="Remove this bidang allocation" title="Remove bidang allocation"><i class="bi bi-trash" aria-hidden="true"></i></button>
                    </div>
                </div>
            </div>`;
        plotSizeRow.querySelector('.bidang-rows-container').insertAdjacentHTML('beforeend', html);
        updateSizeRemainingBadge(plotSizeRow);
        calculateAllocations();
    }

    // ── Add Location row ───────────────────────────────────
    document.getElementById('addSizeBtn').addEventListener('click', function() {
        const idx = sizeCounter++;
        const html = `
            <div class="plot-size-row mb-3" data-size-idx="${idx}" style="border:1px solid var(--ui-border);border-radius:12px;overflow:hidden;">
                <div class="p-3 px-4" style="background:var(--ui-surface-2);">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-5">
                            <label class="mac-label">Location <span class="text-danger">*</span></label>
                            <input type="text" name="plot_sizes[${idx}][location]" class="mac-form-control plot-location" placeholder="e.g., B" required>
                        </div>
                        <div class="col-md-3">
                            <label class="mac-label">Quantity <span class="text-danger">*</span></label>
                            <input type="number" name="plot_sizes[${idx}][quantity]" class="mac-form-control plot-quantity" placeholder="e.g., 30" required min="1" value="1">
                        </div>
                        <div class="col-md-3">
                            <label class="mac-label" title="The plots no allocation below claims. They are the open pool, drawn by anyone whose code has run out — at this size.">Size of the rest</label>
                            <select name="plot_sizes[${idx}][size]" class="mac-select plot-leftover-size" required>
                                ${getSizesHtml()}
                            </select>
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="button" class="mac-action-btn-danger mac-action-btn-field remove-size" aria-label="Remove this location" title="Remove location"><i class="bi bi-trash" aria-hidden="true"></i></button>
                        </div>
                    </div>
                </div>
                <div class="bidang-sub-container p-3" style="border-top:1px solid var(--ui-sep);background:var(--ui-surface-2);">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <small class="fw-semibold" style="color:var(--ui-text-2);">Bidang Allocations</small>
                            <span class="size-remaining-badge mac-badge mac-badge-gray">1 remaining</span>
                        </div>
                        <button type="button" class="add-bidang-btn mac-btn-secondary" style="font-size:0.8125rem;padding:4px 10px;">
                            <i class="bi bi-plus"></i> Add Bidang
                        </button>
                    </div>
                    <div class="bidang-rows-container"></div>
                </div>
            </div>`;
        document.getElementById('plotSizesContainer').insertAdjacentHTML('beforeend', html);
        reindexPlotSizes();
        calculateTotals();
    });

    // ── Confirm the two removals that take real work with them ────
    // A location takes every allocation nested under it, so the prompt says
    // how many rather than leaving the admin to notice afterwards.
    function confirmRemoveLocation(plotSizeRow) {
        const name = plotSizeRow.querySelector('.plot-location')?.value.trim() || 'this location';
        const n = plotSizeRow.querySelectorAll('.bidang-allocation-row').length;
        return confirm(`Remove location "${name}"?`
            + (n ? `

Its ${n} bidang allocation${n > 1 ? 's' : ''} will be removed with it.` : ''));
    }

    function confirmRemoveBidang(bidangRow) {
        const code = bidangRow.querySelector('.bidang-select')?.selectedOptions[0]?.text.trim() || '';
        const plots = bidangRow.querySelector('.plots-count')?.value || '';
        return confirm(code && !code.startsWith('--')
            ? `Remove the allocation of ${plots} plot(s) to ${code}?`
            : 'Remove this bidang allocation?');
    }

    // ── Event delegation (remove location, add/remove bidang) ─
    document.getElementById('plotSizesContainer').addEventListener('click', function(e) {
        if (e.target.closest('.remove-size')) {
            const row = e.target.closest('.plot-size-row');
            if (document.querySelectorAll('.plot-size-row').length > 1 && confirmRemoveLocation(row)) {
                row.remove();
                reindexPlotSizes();
                calculateTotals();
            }
        }
        if (e.target.closest('.add-bidang-btn')) {
            addBidangRowTo(e.target.closest('.plot-size-row'));
        }
        if (e.target.closest('.remove-bidang')) {
            const bidangRow = e.target.closest('.bidang-allocation-row');
            const plotSizeRow = bidangRow.closest('.plot-size-row');
            if (!confirmRemoveBidang(bidangRow)) return;
            bidangRow.remove();
            updateSizeRemainingBadge(plotSizeRow);
            calculateAllocations();
        }
    });

    document.getElementById('plotSizesContainer').addEventListener('change', function(e) {
        if (e.target.matches('.main-category-select')) {
            updateCodeDropdown(e.target);
            // Rebuilt from scratch, so the codes it just put back have to be
            // narrowed again to what is still short.
            refreshCodeChoices();
        }
        // Picking the code is what decides which chip the plots count towards,
        // and how many plots the code is asking for in the first place. The size
        // is the other half of that key — "RKP 3x3" and "RKP 3x6" are two
        // demands — so changing either re-asks the same question.
        if (e.target.matches('.bidang-select') || e.target.matches('.bidang-size')) {
            if (e.target.matches('.bidang-select')) pickShortSize(e.target.closest('.bidang-allocation-row'));
            autofillPlots(e.target.closest('.bidang-allocation-row'));
            updateSizeRemainingBadge(e.target.closest('.plot-size-row'));
            calculateAllocations();
        }
    });

    document.getElementById('plotSizesContainer').addEventListener('input', function(e) {
        if (e.target.matches('.plot-quantity')) calculateTotals();
        if (e.target.matches('.plots-count')) {
            updateSizeRemainingBadge(e.target.closest('.plot-size-row'));
            calculateAllocations();
        }
    });

    // ── Initialise ─────────────────────────────────────────
    calculateTotals();
    document.querySelectorAll('.main-category-select').forEach(sel => { if (sel.value) updateCodeDropdown(sel); });
    // After the code lists exist: calculateTotals() ran before them.
    refreshCodeChoices();
    refreshCategoryChoices();

    // Soft confirm — no bidang allocations means no craft code matching
    document.getElementById('pollingForm').addEventListener('submit', function(e) {
        if (document.querySelectorAll('.bidang-allocation-row').length === 0) {
            const proceed = confirm(
                'Warning: No bidang allocations added.\n\n' +
                'Plots will be created without craft category codes. ' +
                'Lot assignment will be random and will not match companies by their craft category.\n\n' +
                'Proceed without bidang allocations?'
            );
            if (!proceed) {
                e.preventDefault();
                return false;
            }
        }
    });

});
</script>
