<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $primaryKey = 'key';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['key', 'value'];

    /**
     * Read a setting, at most one SELECT per key per request.
     *
     * The memo is the `settings.memo` scoped binding in `AppServiceProvider`, which
     * carries the reasoning; the short version is that callers read the same key once
     * per rendered row, and a scoped binding cannot outlive its request the way a
     * `static` property would under Octane.
     *
     * A key that has no row memoises as null, so a missing setting costs one SELECT per
     * request rather than one per read — that is the FAQ case, where nothing is stored
     * and every row was still asking.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $memo = app('settings.memo');

        if (! isset($memo[$key])) {
            $memo[$key] = ['value' => static::find($key)?->value];
        }

        return $memo[$key]['value'] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /**
     * Any write through the model drops that key's memo, so a request that writes and
     * then reads cannot be served the value from before its own write.
     *
     * On the model rather than inside set(): create(), update() and delete() are all
     * used directly, and the audit that says "every writer redirects" is true today and
     * is not a thing to keep re-verifying.
     *
     * Query-builder writes fire no events and bypass this, as they bypass every model
     * event. The one in the codebase is CalendarPalette::reset()'s
     * `Setting::whereIn(...)->delete()`, and it cannot leave a stale entry here because
     * CalendarPalette reads its keys with its own whereIn, never through get().
     */
    protected static function booted(): void
    {
        $forget = function (self $setting) {
            $memo = app('settings.memo');

            if (isset($memo[$setting->key])) {
                unset($memo[$setting->key]);
            }
        };

        static::saved($forget);
        static::deleted($forget);
    }
}
