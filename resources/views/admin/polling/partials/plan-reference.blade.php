{{--
    How many plots to build is a question about how many entrants there are. A
    site that fits 20 gives 20 entrants one 3x3 each; the same site gives 10
    entrants room for a 3x6 or a 3x9. This sits above the quantity fields so the
    number is in front of the planner, not on the registrations page.

    Every chip carries TWO numbers — holders and plots — because one was the
    whole problem. The card knew the demand, the form below knew the supply, and
    nothing on the page subtracted one from the other: TSA read "· 11" in exactly
    the style of the twenty-five codes that were covered, and eighteen people
    lost their craft category while their chip looked accounted for.

    One chip per code AND booth size — "RKP 3x3", "RKP 3x6" — because that is
    what the draw list states and what the allocations below are typed
    against. A single per-code total reads as met while one of its two sizes has
    nothing at all.

    Expects:
      $drawCount        — registrations in the event's draw
      $drawByBidang — bidang name => ("code size" => ['bidang_id', 'size', 'needed', 'have'])
      $hasPlots         — false while the plan is still being typed
--}}
<div class="mac-card mb-4" style="background:var(--ui-blue-tint);border-color:var(--ui-border);">
    <div class="mac-card-padded">
        <div class="d-flex align-items-center flex-wrap gap-4">
            <div>
                <div class="mac-label mb-1">In the draw</div>
                <div style="font-size:1.75rem;font-weight:700;color:var(--ui-text);line-height:1;">
                    {{ $drawCount }}
                </div>
            </div>

            <div style="width:1px;height:44px;background:var(--ui-border);"></div>

            <div>
                <div class="mac-label mb-1">Plots planned</div>
                <div id="planRefPlots" style="font-size:1.75rem;font-weight:700;color:var(--ui-text);line-height:1;">0</div>
            </div>

            <div style="width:1px;height:44px;background:var(--ui-border);"></div>

            <div style="flex:1;min-width:220px;">
                <div class="mac-label mb-1">Per company</div>
                <div id="planRefRatio" style="font-size:0.875rem;color:var(--ui-text-2);line-height:1.5;">—</div>
            </div>
        </div>

        <hr style="border-color:var(--ui-border);">

        @if($drawCount === 0)
            <small class="mac-muted">
                Nobody is in this event's draw yet. Plots can still be planned, but polling
                will not open until every company in the draw carries a craft code.
            </small>
        @else
            <div class="d-flex justify-content-between align-items-baseline mb-2">
                <div class="mac-label">By craft code &amp; booth size <span class="mac-muted" style="font-weight:400;">— holders / plots</span></div>
                <small class="mac-muted" id="coverageSummary"></small>
            </div>

            <div class="d-flex flex-column gap-2">
                @foreach($drawByBidang as $bidangName => $codes)
                    <div class="d-flex align-items-baseline flex-wrap gap-2">
                        <span style="min-width:150px;font-size:0.8125rem;font-weight:600;color:var(--ui-text-2);">
                            {{ $bidangName }}
                            <span class="mac-muted" style="font-weight:400;">({{ collect($codes)->sum('needed') }})</span>
                        </span>
                        @foreach($codes as $code => $c)
                            @php
                                // Red is nobody gets their category; orange is some
                                // do; green is covered. A code with no bidang row at
                                // all cannot be counted either way.
                                $class = $code === '—'
                                    ? 'mac-badge-red'
                                    : ($c['have'] === 0
                                        ? 'mac-badge-red'
                                        : ($c['have'] < $c['needed'] ? 'mac-badge-orange' : 'mac-badge-green'));
                            @endphp
                            {{-- gap, because .mac-badge is inline-flex and eats the
                                 whitespace between the label and the tally. Harmless
                                 while the label was "TBP"; with the size on it,
                                 "RKP 3x6" and "4 / 0" ran together as "RKP 3x64 / 0". --}}
                            <span class="mac-badge {{ $class }} coverage-chip" style="gap:5px;"
                                  data-bidang="{{ $c['bidang_id'] }}"
                                  data-size="{{ $c['size'] ?? '' }}"
                                  data-needed="{{ $c['needed'] }}"
                                  title="{{ $code === '—' ? 'No craft code assigned' : $code.' — '.$c['needed'].' in the draw want it'.($c['size'] ? ' at '.$c['size'] : ' at any size').', '.$c['have'].' plot(s) match' }}">
                                {{ $code === '—' ? 'No code yet' : $code }}
                                <span class="chip-tally">{{ $c['needed'] }}{{ $code === '—' ? '' : ' / '.$c['have'] }}</span>
                            </span>
                        @endforeach
                    </div>
                @endforeach
            </div>

            @if($drawByBidang->contains(fn ($codes) => collect($codes)->has('—')))
                <small class="mac-muted d-block mt-2">
                    A registration with no craft code wins nothing of its own — it draws from the
                    uncoded plots, and polling will not open while any is left without one.
                </small>
            @endif
        @endif
    </div>
</div>
