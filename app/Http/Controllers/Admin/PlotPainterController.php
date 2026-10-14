<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BidangAllocation;
use App\Models\Event;
use App\Models\KrafBidang;
use App\Models\Plot;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Put craft codes on plots by clicking them.
 *
 * The typed plan asks "how many plots for RKP" and answers it out of sight;
 * whether every code someone in the draw holds actually got plots is a
 * subtraction nobody performs. In `daftar` it went unperformed — two codes left
 * out of twenty-five, eighteen people seated in uncoded plots, and the only
 * notice an amber "18 not yet assigned".
 *
 * Painting cannot hide that: an unpainted square is visibly unpainted, and a
 * code with nobody's square on it reads zero in its own chip.
 *
 * What it does NOT change is the plot_code rule. After a save the plots are
 * renumbered with Plot::codeFor(), the rule doGeneratePlots() uses, so a painted
 * RKP plot is still HALL A-RKP1.
 *
 * Gated `can:manage-events` in routes/web.php: painting is a change to the plan.
 */
class PlotPainterController extends Controller
{
    public function edit(Event $event)
    {
        $event->load('plotSizes');

        // In plot_code order, naturally: the squares are read as a row of numbers.
        $plots = $event->plots()->with('bidang:id,code')->get()
            ->sortBy('plot_code', SORT_NATURAL)
            ->groupBy('plot_size_id');

        // The palette: every code someone in the draw holds, and how many hold
        // it. A code nobody holds is not offered — painting it would build plots
        // for a category with no one in it.
        $codeRows = KrafBidang::codes()->get()->keyBy('code');
        $entries = $event->registrations()->get();

        $demand = $entries
            ->filter(fn ($r) => filled($r->code) && $codeRows->has($r->code))
            ->groupBy('code')
            ->map(fn ($rows, $code) => ['bidang' => $codeRows[$code], 'needed' => $rows->count()])
            ->sortBy(fn ($d) => $d['bidang']->bil)
            ->values();

        return view('admin.polling.paint', [
            'event' => $event,
            'plots' => $plots,
            'demand' => $demand,
            // No code, or one the coding sheet does not have: these win nothing
            // of their own whatever is painted.
            'uncoded' => $entries->reject(fn ($r) => filled($r->code) && $codeRows->has($r->code))->count(),
            'frozen' => $this->frozen($event),
        ]);
    }

    public function update(Request $request, Event $event)
    {
        if ($this->frozen($event)) {
            return back()->withErrors(['error' =>
                'Lots are already held in this draw, so the codes on its plots are locked. '
                .'Remove those lots first if they genuinely have to change.']);
        }

        $validated = $request->validate([
            // plot id => kraf_bidangs id, or empty for "leave it uncoded".
            'paint' => 'required|array',
            'paint.*' => ['nullable', 'integer'],
        ]);

        // The VALUE is what gets written, into a foreign key, and nothing else
        // checks it. One query over the distinct codes — and CODES only: a
        // category row painted on a plot would match no registration ever.
        $ids = array_values(array_unique(array_filter($validated['paint'])));
        $known = KrafBidang::codes()->whereIn('id', $ids)->pluck('code', 'id');

        if ($known->count() !== count($ids)) {
            throw ValidationException::withMessages([
                'paint' => 'A craft code painted on that board is not on the coding sheet. Reload the page and paint it again.',
            ]);
        }

        $plots = $event->plots()->with('plotSize')->get()->keyBy('id');
        $painted = 0;

        DB::transaction(function () use ($event, $validated, $plots, $known, &$painted) {
            foreach ($validated['paint'] as $plotId => $bidangId) {
                // Ids are posted, so a plot from another event can be named. It
                // is not in $plots, and is skipped.
                $plot = $plots[(int) $plotId] ?? null;

                if (! $plot) {
                    continue;
                }

                // Set, not saved: renumber() saves once with both fields dirty —
                // one UPDATE and one audit row per plot, not two of each.
                $plot->bidang_id = $bidangId ?: null;
                $painted++;
            }

            $this->renumber($plots, $known);
            $this->rebuildAllocations($event);
        });

        return redirect()
            ->route('admin.polling.paint', $event)
            ->with('success', "Codes saved on {$painted} plot(s), and the plot numbers rebuilt to match.");
    }

    /**
     * A held plot cannot be repainted: moving the code under it would move its
     * company into a category nobody put them in. The same freeze the typed plan
     * honours.
     */
    private function frozen(Event $event): bool
    {
        return $event->plots()->where('is_allocated', true)->exists();
    }

    /**
     * Rewrite every plot_code from what is now painted on it. The counter is per
     * location and token, as in doGeneratePlots().
     *
     * $known maps id => code for every code posted; a plot's cached `bidang`
     * relation still describes its OLD bidang_id, so it cannot be asked. Only a
     * plot the form did not post falls back to a fresh lookup.
     */
    private function renumber(Collection $plots, Collection $known): void
    {
        $counters = [];

        foreach ($plots->sortBy('plot_code', SORT_NATURAL) as $plot) {
            $location = $plot->plotSize->location;

            // The craft code where it has one; the size where it does not.
            $token = $plot->bidang_id
                ? ($known[$plot->bidang_id] ?? KrafBidang::whereKey($plot->bidang_id)->value('code'))
                : $plot->plotSize->size;

            $key = ($location ?: 'LOC').'|'.$token;
            $counters[$key] = ($counters[$key] ?? 0) + 1;

            $plot->plot_code = Plot::codeFor($location, $token, $counters[$key]);
            $plot->save();
        }
    }

    /**
     * bidang_allocations is not the input any more, but the plan screens still
     * read it. Derived from the painting, or they describe a plan that no longer
     * exists.
     */
    private function rebuildAllocations(Event $event): void
    {
        $event->bidangAllocations()->delete();

        $event->plots()->whereNotNull('bidang_id')->get()
            ->groupBy(fn ($p) => $p->plot_size_id.'|'.$p->bidang_id)
            ->each(fn ($group) => BidangAllocation::create([
                'event_id' => $event->id,
                'plot_size_id' => $group->first()->plot_size_id,
                'bidang_id' => $group->first()->bidang_id,
                'plots_count' => $group->count(),
            ]));
    }
}
