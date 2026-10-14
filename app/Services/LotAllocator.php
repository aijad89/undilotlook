<?php

namespace App\Services;

use App\Exceptions\LotUnavailable;
use App\Models\EventRegistration;
use App\Models\Plot;
use App\Models\Poll;

/**
 * Draws one lot for one registration.
 *
 * Both doors come through here: the participant's Confirm button and the
 * admin's bulk run (PlotAllocationAlgorithm). They once held separate copies of
 * these rules and the copies drifted — one honoured a booth size and the other
 * ignored it, one wrote a Poll and the other did not. What still separates the
 * two paths is recorded where it belongs: plots.allocation_type says whether a
 * lot was preset or drawn, not which button was pressed.
 *
 * The caller owns the transaction: Confirm wraps one call, the bulk run wraps
 * many, and a failure inside either has to roll its own work back.
 */
class LotAllocator
{
    /**
     * @throws LotUnavailable when nothing can be drawn, with a reason the caller
     *                        can show to a participant or file against a company.
     */
    public function assign(EventRegistration $registration): Plot
    {
        // Checked first, because polls.user_id is NOT NULL: without this the
        // insert below fails as a database error, which is not a LotUnavailable,
        // so the bulk run would roll back every lot it had already handed out.
        if (! $registration->user_id) {
            throw LotUnavailable::noAccount();
        }

        $plot = $registration->plot_id
            ? $this->takePreset($registration)
            : $this->draw($registration);

        $plot->update([
            'company_id' => $registration->company_id,
            'allocated_to' => $registration->user_id,
            'is_allocated' => true,
            'allocation_type' => $registration->plot_id ? 'preset' : 'algorithm',
            'allocated_at' => now(),
        ]);

        $registration->update([
            'plot_id' => $plot->id,
            'plot_size' => $registration->plot_size ?: $plot->plotSize?->size,
            'confirmed_participation' => true,
            'confirmed_at' => now(),
        ]);

        // One row per drawn lot, whoever set it off. "Has this company drawn
        // yet" is asked of this table, and it only has one answer if both doors
        // write it.
        Poll::firstOrCreate(
            ['user_id' => $registration->user_id, 'event_id' => $registration->event_id],
            ['code' => $registration->code],
        );

        return $plot;
    }

    /**
     * A lot the admin set aside for this company before the draw opened. It is
     * already theirs; all that is left is to check it is still real.
     */
    private function takePreset(EventRegistration $registration): Plot
    {
        $plot = Plot::lockForUpdate()->find($registration->plot_id);

        if (! $plot) {
            throw LotUnavailable::presetMissing();
        }

        if ($plot->event_id !== $registration->event_id) {
            throw LotUnavailable::presetWrongEvent();
        }

        // Held by somebody else means the reservation is gone.
        if ($plot->is_allocated && $plot->allocated_to !== $registration->user_id) {
            throw LotUnavailable::presetMissing();
        }

        // is_allocated cannot say "already drawn": a preset reservation sets
        // is_allocated and allocated_to at the moment it reserves, which is
        // exactly the state an untouched reservation is in. Reading them that way
        // refused every preset holder their own lot.
        //
        // Poll is the record that means it — written only in assign(), at the
        // moment a lot changes hands, and never by a reservation.
        $alreadyDrawn = Poll::where('user_id', $registration->user_id)
            ->where('event_id', $registration->event_id)
            ->exists();

        if ($alreadyDrawn) {
            throw LotUnavailable::alreadyDrawn();
        }

        return $plot;
    }

    private function draw(EventRegistration $registration): Plot
    {
        // The registration's code is a STRING matched against the code on the
        // `kraf_bidangs` row the plot is categorised by. Renaming a code in
        // Settings therefore strands everyone still holding the old spelling —
        // known, and accepted: the string is what the office types and reads.
        //
        // A blank code is not "match anything". It can win nothing of its own
        // and goes to the open pool below.
        if (filled($registration->code)) {
            $plot = $this->sized($this->freePlots($registration), $registration)
                ->whereHas('bidang', fn ($b) => $b->where('code', $registration->code))
                ->inRandomOrder()
                ->lockForUpdate()
                ->first();

            if ($plot) {
                return $plot;
            }

            // Falls through to the open pool, bound to the same size. The size
            // is honoured; only the craft code is given up — which is what
            // PollingGuard counts on when it lets a code with too few lots open.
        }

        // The open pool: plots no code can claim. Only those — taking a plot that
        // belongs to another code would seat this company by unseating one the
        // draw had already accounted for.
        //
        // "No code can claim it" is wider than bidang_id being null: a plot tagged
        // to a craft CATEGORY row (code IS NULL) is unmatchable by every
        // registration, so it belongs in the pool rather than sitting unwinnable.
        $plot = $this->sized($this->freePlots($registration), $registration)
            ->where(fn ($q) => $q
                ->whereNull('bidang_id')
                ->orWhereHas('bidang', fn ($b) => $b->whereNull('code')->orWhere('code', '')))
            ->inRandomOrder()
            ->lockForUpdate()
            ->first();

        if (! $plot) {
            throw $registration->plot_size
                ? LotUnavailable::sizeExhausted()
                : LotUnavailable::noneForCode();
        }

        return $plot;
    }

    private function freePlots(EventRegistration $registration)
    {
        return Plot::where('event_id', $registration->event_id)
            ->where('is_allocated', false)
            ->where('is_locked', false);
    }

    /** The registration's booth size, where one is set. It binds both pools equally. */
    private function sized($query, EventRegistration $registration)
    {
        return $query->when(
            $registration->plot_size,
            fn ($q) => $q->whereHas('plotSize', fn ($s) => $s->where('size', $registration->plot_size)),
        );
    }
}
