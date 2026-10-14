<?php

namespace App\Support;

use App\Models\Event;

/**
 * The calendar's colours, in one place: the legend, the bars and the end caps
 * all read from here, so they cannot disagree.
 *
 * `daftar` stored these in Settings with a screen to edit them. undilot has no
 * such screen, so they are constants until somebody asks to change one.
 */
class CalendarPalette
{
    // Each value mirrors a token in partials/ui-tokens.blade.php (named alongside):
    // these reach the page as data and inline styles, where CSS variables do not.
    public const PALETTE = [
        'event_open' => '#12804A',              // --ui-ok
        'event_closed' => '#C4320A',            // --ui-bad
        'event_completed' => '#5B3FB3',         // --ui-violet-text
        'event_draft' => '#586477',             // --ui-muted
        'registration_fill' => '#EEF1F5',       // --ui-neutral-tint
        'registration_border' => '#C3CCD8',     // --ui-border-strong
        'registration_text' => '#344054',       // --ui-text-2
        'registration_open_cap' => '#12804A',   // --ui-ok
        'registration_close_cap' => '#C4320A',  // --ui-bad
        'polling' => '#1A56DB',                 // --ui-blue: voting is the thing to act on
    ];

    /** What an admin may pick for one event, so concurrent windows can be told apart. */
    public const EVENT_COLOURS = [
        '#E53935' => 'Red',
        '#D81B60' => 'Pink',
        '#8E24AA' => 'Purple',
        '#3949AB' => 'Indigo',
        '#1E88E5' => 'Blue',
        '#00897B' => 'Teal',
        '#43A047' => 'Green',
        '#FB8C00' => 'Orange',
        '#6D4C41' => 'Brown',
        '#546E7A' => 'Blue Grey',
    ];

    /** The event's own colour where one was picked, else its status colour. */
    public static function forEvent(Event $event): string
    {
        return array_key_exists((string) $event->colour, self::EVENT_COLOURS)
            ? $event->colour
            : (self::PALETTE['event_'.$event->status] ?? self::PALETTE['event_draft']);
    }

    /** A pale tint of a colour — the body of that event's registration bar. */
    public static function tint(string $hex, float $weight = 0.15): string
    {
        [$r, $g, $b] = sscanf($hex, '#%02x%02x%02x');
        $mix = fn ($c) => (int) round($c * $weight + 255 * (1 - $weight));

        return sprintf('#%02X%02X%02X', $mix($r), $mix($g), $mix($b));
    }
}
