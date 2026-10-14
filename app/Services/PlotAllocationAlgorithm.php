<?php

namespace App\Services;

use App\Exceptions\LotUnavailable;
use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * The admin's bulk draw: everyone still without a lot, in one go.
 *
 * It decides nothing. It picks who is eligible, shuffles them for fairness, and
 * calls LotAllocator once each — the same call the Confirm button makes. Anyone
 * it cannot place comes back named, instead of falling out of the result.
 */
class PlotAllocationAlgorithm
{
    public function __construct(private LotAllocator $allocator = new LotAllocator) {}

    public function run(Event $event): array
    {
        return DB::transaction(function () use ($event) {
            $result = $this->allocatePlots($event);

            // Completed means everyone has a lot. "completed" is a dead end in the
            // status map, so marking a run done while companies are still waiting
            // would leave no way back to place them.
            if (empty($result['waiting_list'])) {
                $event->update(['polling_status' => 'completed']);
            }

            Log::info('Lot draw run', ['event_id' => $event->id] + $result);

            return $result;
        });
    }

    private function allocatePlots(Event $event): array
    {
        // Everyone in the draw and not yet holding a lot. The row's existence is
        // the approval, so there is no status to filter on.
        //
        // confirmed_participation is deliberately NOT a condition: the draw sets
        // it, at the moment a lot is handed over. Requiring it beforehand would
        // empty this pool of exactly the people the bulk run exists to place.
        //
        // A preset row carries plot_id already and is skipped: that company is
        // seated, and LotAllocator would only confirm what it already has.
        $eligible = EventRegistration::where('event_id', $event->id)
            ->whereNull('plot_id')
            ->with('company')
            ->get()
            // Shuffled, not ordered: the order lots are handed out in is the only
            // thing separating two companies competing for the same last lot.
            ->shuffle();

        $allocated = 0;
        $waitingList = [];

        foreach ($eligible as $registration) {
            try {
                $this->allocator->assign($registration);
                $allocated++;
            } catch (LotUnavailable $e) {
                $waitingList[] = $registration->company->name;

                Log::warning('Could not draw a lot', [
                    'registration_id' => $registration->id,
                    'code' => $registration->code,
                    'plot_size' => $registration->plot_size,
                    'reason' => $e->reason,
                ]);
            }
        }

        return [
            'allocated' => $allocated,
            'waiting_list' => $waitingList,
        ];
    }
}
