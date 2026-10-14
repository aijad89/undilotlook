<?php

namespace App\Support;

use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Plot;

/**
 * Why this draw cannot open yet, or null if it can.
 *
 * Kept out of the controller so that every door which opens a draw asks the
 * same questions — in `daftar` the scheduler opened draws by a mass update and
 * ran none of them, and the first participant to press Confirm found out.
 * It returns a reason rather than a response, so each caller words the refusal
 * its own way.
 *
 * These are checks about whether the draw can FINISH. A draw that can finish
 * but finishes badly — someone seated outside their own craft — is a warning,
 * shown beside the button, because it is a judgement an admin makes.
 *
 * Every registration of the event is in the draw: the row is the approval.
 */
class PollingGuard
{
    public static function blocker(Event $event): ?string
    {
        return static::noPlots($event)
            ?? static::missingAccounts($event)
            ?? static::missingCodes($event)
            ?? static::codesWithNoPlot($event)
            ?? static::sizesWithTooFewLots($event);
    }

    private static function noPlots(Event $event): ?string
    {
        return Plot::where('event_id', $event->id)->exists()
            ? null
            : 'Cannot start polling: no plots have been created for this event yet.';
    }

    /** A drawn lot is recorded against the account; a company without one can win nothing. */
    private static function missingAccounts(Event $event): ?string
    {
        $missing = EventRegistration::where('event_id', $event->id)
            ->whereNull('plot_id')
            ->whereNull('user_id')
            ->count();

        return $missing === 0
            ? null
            : "Cannot start polling: {$missing} company(ies) in the draw have no participant account yet.";
    }

    /** The allocator matches by craft code, so a blank code silently wins nothing. */
    private static function missingCodes(Event $event): ?string
    {
        $missing = EventRegistration::where('event_id', $event->id)
            ->where(fn ($q) => $q->whereNull('code')->orWhere('code', ''))
            ->count();

        return $missing === 0
            ? null
            : "Cannot start polling: {$missing} registration(s) in the draw still have no craft code.";
    }

    /**
     * A code that names no plot loses just as silently as a blank one. The open
     * pool covers some of the shortfall — only what it cannot cover is worth
     * blocking on.
     */
    private static function codesWithNoPlot(Event $event): ?string
    {
        $plotCodes = static::plotCodes($event);

        // Anyone already holding a plot is not waiting on one. A preset is exactly
        // that, and need not respect the code.
        $unmatched = EventRegistration::where('event_id', $event->id)
            ->whereNull('plot_id')
            ->get()
            ->reject(fn ($reg) => filled($reg->code) && $plotCodes->contains($reg->code));

        // Every open-pool plot, allocated or not — unlike the size check below,
        // which counts only free ones. The set above already drops anyone holding
        // a plot, so both sides here include what has been handed out; narrowing
        // one side and not the other would refuse draws that are fine.
        $openPlots = static::openPool($event, freeOnly: false)->count();

        if ($unmatched->count() <= $openPlots) {
            return null;
        }

        $codes = $unmatched->pluck('code')->filter()->unique()->sort()->implode(', ');
        $short = $unmatched->count() - $openPlots;

        return "Cannot start polling: {$unmatched->count()} registration(s) carry a craft code with no plot to win — {$codes}. "
            .($openPlots > 0 ? "Only {$openPlots} uncoded plot(s) are available to absorb them, leaving {$short} with nothing. " : '')
            .'Add plots for those codes, or change the codes on those registrations.';
    }

    /**
     * A registration's booth size binds it: it draws from that size and no other.
     * The craft code IS given up when the code runs out, to the open pool, so the
     * pool counts here exactly as it does above.
     *
     * Per size rather than per (code, size): the open pool is shared, so two codes
     * each short by one at 3x3 are two short against one pool, not one short twice.
     */
    private static function sizesWithTooFewLots(Event $event): ?string
    {
        $wanted = [];   // size => plots still wanted after its own code's
        $named = [];    // size => ["RKP 3x3 (need 3, have 1)", …]

        $sized = EventRegistration::where('event_id', $event->id)
            ->whereNull('plot_id')
            ->whereNotNull('plot_size')
            ->whereNotNull('code')
            ->where('code', '!=', '')
            ->get()
            ->groupBy(fn ($r) => $r->code.'|'.$r->plot_size);

        // Every free coded lot, counted once and keyed the same way as $sized.
        $freeByKey = $event->plots()
            ->join('plot_sizes', 'plots.plot_size_id', '=', 'plot_sizes.id')
            ->join('kraf_bidangs', 'plots.bidang_id', '=', 'kraf_bidangs.id')
            ->where('plots.is_allocated', false)
            ->where('plots.is_locked', false)
            ->selectRaw('kraf_bidangs.code, plot_sizes.size, count(*) as n')
            ->groupBy('kraf_bidangs.code', 'plot_sizes.size')
            ->get()
            ->mapWithKeys(fn ($r) => [$r->code.'|'.$r->size => (int) $r->n]);

        $poolBySize = static::openPool($event)
            ->join('plot_sizes', 'plots.plot_size_id', '=', 'plot_sizes.id')
            ->selectRaw('plot_sizes.size, count(*) as n')
            ->groupBy('plot_sizes.size')
            ->pluck('n', 'size');

        foreach ($sized as $key => $regs) {
            [$code, $size] = explode('|', $key, 2);

            $free = $freeByKey[$key] ?? 0;

            if ($regs->count() <= $free) {
                continue;
            }

            $wanted[$size] = ($wanted[$size] ?? 0) + ($regs->count() - $free);
            $named[$size][] = "{$code} {$size} (need {$regs->count()}, have {$free})";
        }

        $shortfalls = collect($wanted)
            ->filter(fn ($short, $size) => $short > (int) ($poolBySize[$size] ?? 0))
            ->flatMap(fn ($short, $size) => $named[$size])
            ->values();

        return $shortfalls->isEmpty() ? null
            : 'Cannot start polling: not enough lots for the booth sizes set on some registrations — '
              .$shortfalls->implode('; ').'. '
              .'Allocate more of those sizes, or change the booth size on those registrations.';
    }

    /** The distinct craft codes this event's plots are categorised by. */
    private static function plotCodes(Event $event)
    {
        return $event->plots()
            ->join('kraf_bidangs', 'plots.bidang_id', '=', 'kraf_bidangs.id')
            ->whereNotNull('kraf_bidangs.code')
            ->where('kraf_bidangs.code', '!=', '')
            ->distinct()
            ->pluck('kraf_bidangs.code');
    }

    /**
     * The plots no code can claim. Wider than bidang_id being null: a plot tagged
     * to a craft category row (code IS NULL) is unmatchable by every
     * registration, so it belongs in the pool.
     */
    private static function openPool(Event $event, bool $freeOnly = true)
    {
        return $event->plots()
            ->where(fn ($q) => $q
                ->whereNull('bidang_id')
                ->orWhereHas('bidang', fn ($b) => $b->whereNull('code')->orWhere('code', '')))
            ->when($freeOnly, fn ($q) => $q->where('is_allocated', false)->where('is_locked', false));
    }
}
