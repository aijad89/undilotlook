<?php

namespace App\Support;

/**
 * The booth sizes a lot draw is planned in.
 *
 * This was a const on the PlotSize model, which is the wrong place for it: the
 * list decides what a draw entry's `event_registrations.plot_size` may be set
 * to AND what an event's plan is typed against. The two have to agree exactly
 * for a plan row to pair with the registrations it seats, and neither should
 * need a deploy to change.
 *
 * The list is offered, not enforced. A size already in an event's plan or on a
 * registration is still shown beside these — removing 3x9 here must not silently
 * strand the draw that was planned in it.
 */
class PlotSizes extends ManagedList
{
    protected const KEY = 'plot_sizes';

    public const DEFAULTS = [
        '3x3',
        '3x6',
        '3x9',
    ];
}
