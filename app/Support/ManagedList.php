<?php

namespace App\Support;

use App\Models\Setting;

/**
 * A simple ordered list of strings that a super admin can edit, stored as JSON
 * in a single `settings` row.
 *
 * Each subclass names its settings key and its fallback values, so a fresh
 * install works with no settings rows present at all and the code never has to
 * cope with an empty list.
 *
 * Deliberately not a database table: these lists are short, rarely change, have
 * no attributes beyond their own text, and nothing references them by id. A
 * table would add a migration, a model and a join for no gain. Certifications
 * are the counter-example — company records reference them by id, so they stay
 * a real table. CertificateTypes, which sits beside them in Settings, is the
 * near miss: same subject, but stored as free text, so it belongs here.
 */
abstract class ManagedList
{
    /** The settings key this list is stored under. */
    protected const KEY = '';

    /**
     * Values used when nothing has been saved yet.
     *
     * @var array<int, string>
     */
    public const DEFAULTS = [];

    /** @return array<int, string> */
    public static function all(): array
    {
        $raw = Setting::get(static::KEY);

        if (! $raw) {
            return static::DEFAULTS;
        }

        $decoded = json_decode($raw, true);

        // A malformed row must not empty every dropdown in the app.
        if (! is_array($decoded) || $decoded === []) {
            return static::DEFAULTS;
        }

        return array_values(array_map('strval', $decoded));
    }

    /**
     * Replace the whole list. Values are trimmed, blanks dropped and duplicates
     * removed, so the UI can post a textarea or a repeated field without
     * sanitising first.
     *
     * @param  array<int, string>  $values
     */
    public static function set(array $values): void
    {
        $clean = [];

        foreach ($values as $value) {
            $value = trim((string) $value);
            if ($value !== '' && ! static::containsSame($clean, $value)) {
                $clean[] = $value;
            }
        }

        if ($clean === []) {
            // Refuse to store an empty list; an empty dropdown is never the
            // intent and would block the forms that depend on it.
            return;
        }

        Setting::set(static::KEY, json_encode(array_values($clean)));
    }

    public static function reset(): void
    {
        Setting::set(static::KEY, json_encode(static::DEFAULTS));
    }

    public static function has(string $value): bool
    {
        return static::containsSame(static::all(), $value);
    }

    /**
     * Is $value already in $list, ignoring case?
     *
     * Case-insensitive on purpose, and it is not a nicety. These lists name
     * values that live in database columns and are compared there in SQL — an
     * admin zone is matched against admins.state, companies.state and
     * events.state, and the connection collates utf8mb4_unicode_ci. MySQL
     * cannot tell "JOHOR" from "Johor", so a list that holds both describes a
     * distinction the data cannot make: one entry reads as unused while the
     * other owns all its rows, and a rename moves rows the screen never
     * counted.
     *
     * Only used for existence and duplicate checks. Validation goes through
     * rule(), which is still an exact Rule::in over the stored spellings.
     */
    protected static function containsSame(array $list, string $value): bool
    {
        $needle = mb_strtolower(trim($value));

        foreach ($list as $item) {
            if (mb_strtolower(trim((string) $item)) === $needle) {
                return true;
            }
        }

        return false;
    }

    /** Convenience for `array_combine()`-style select options. */
    public static function options(): array
    {
        $all = static::all();

        return array_combine($all, $all);
    }

    /**
     * Validation rule bound to the current list.
     *
     * Used instead of a literal `in:...` so that adding an option in Settings
     * takes effect immediately. Previously the list lived in the Blade select
     * *and* in the controller's `in:` rule, so a new option was offered by the
     * form and then rejected on submit.
     */
    public static function rule(): \Illuminate\Validation\Rules\In
    {
        return \Illuminate\Validation\Rule::in(static::all());
    }
}
