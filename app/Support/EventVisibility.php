<?php

namespace App\Support;

use App\Models\Event;

/**
 * May the admin looking at this request open this event's admin screens at all?
 *
 * One rule, state-admin only — `hq` and `super` pass straight through: a draft
 * event is super/hq only. Nothing outside those two roles has a reason to see an
 * event that has not been opened.
 *
 * THERE IS NO OWN-STATE RULE, deliberately. `daftar` had one — a state admin saw
 * an event only once a company from their state was registered for it — and it
 * held there because participants registered themselves. Here the office puts
 * companies into the draw, so that rule would hide every empty event from the
 * state admin who has to fill it: they could never add the first company.
 *
 * WHY THIS IS NOT A GUARD REPEATED IN EVERY CONTROLLER. Several admin screens
 * open an event by id and are reachable by a state admin — the event detail
 * page, the draw list, the polling plan, and the plots-by-size view, which is a
 * separate URL that never routes through any of the others. Gating only the
 * obvious one leaves the rest as back doors to the same event. One predicate,
 * called from each.
 *
 * THIS REJECTS EVENTS; IT DOES NOT FILTER ROWS. A screen that passes this still
 * has to narrow registrations and plots to the admin's own state, and it must
 * keep doing so: this answers "may you open this event", the filter answers
 * "whose rows may you see inside it". Deleting either one leaves a hole the
 * other does not cover.
 */
class EventVisibility
{
    public static function authorize(Event $event): void
    {
        $admin = auth()->guard('admin')->user();

        if (! $admin || $admin->hasGlobalAccess()) {
            return;
        }

        abort_if(
            $event->status === 'draft',
            403,
            'This event has not been opened yet.'
        );
    }
}
