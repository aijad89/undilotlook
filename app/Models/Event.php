<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * An event, and the lot draw that runs inside it.
 *
 * TWO STATUS COLUMNS, TWO LIVES. `status` is whether the event is published;
 * `polling_status` is how far the draw has got. They move independently — a
 * published event's draw can still be a draft, and a draw can be closed while
 * the event runs on. Both vocabularies are consts here and NOT enum columns; see
 * the project rule on enums before thinking about changing that.
 *
 * `state` is an office ZONE (AdminZones), not geography. It is one of the three
 * columns a zone rename rewrites.
 */
class Event extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'title',
        'description',
        'location',
        'state',
        'start_date',
        'end_date',
        'registration_open_at',
        'registration_close_at',
        'auto_open',
        'auto_close',
        'status',
        'polling_status',
        'polling_start_date',
        'polling_deadline',
        'auto_polling_open',
        'auto_polling_close',
        'use_preset',
        'max_participants',
        'banner',
        'colour',
    ];

    /**
     * `total_plots` is deliberately not fillable: it is computed from the plan
     * when the plan is saved, never taken from a form.
     */
    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'registration_open_at' => 'datetime',
        'registration_close_at' => 'datetime',
        'polling_start_date' => 'datetime',
        'polling_deadline' => 'datetime',
        'auto_open' => 'boolean',
        'auto_close' => 'boolean',
        'auto_polling_open' => 'boolean',
        'auto_polling_close' => 'boolean',
        'use_preset' => 'boolean',
    ];

    /**
     * How events.status is worded for admins.
     *
     * Published / Unpublished / Ended, not Open / Closed / Completed: the filter
     * dropdowns and the pill beside them read this same const, and when they read
     * two different lists they disagreed on the same screen — the filter said
     * "Open" while the pill under it said "Published".
     *
     * `cancelled` is deliberately absent: nothing writes it, so offering it as a
     * filter would be an option that can only ever return nothing.
     */
    public const STATUSES = [
        'draft' => 'Draft',
        'open' => 'Published',
        'closed' => 'Unpublished',
        'completed' => 'Ended',
    ];

    /**
     * The stages a lot draw goes through.
     *
     * `polling` reads as "Open" and `completed` as "End" because that is what the
     * admin screens' stepper calls them.
     */
    public const POLLING_STATUSES = [
        'draft' => 'Draft',
        'polling' => 'Open',
        'closed' => 'Closed',
        'completed' => 'End',
        'cancelled' => 'Cancelled',
    ];

    /**
     * The participant's stepper, on the dashboard and the lot page alike. A draft
     * draw is the office's, not yet the participant's, so it starts at Open.
     */
    public const PARTICIPANT_STAGES = ['polling', 'closed', 'completed'];

    public const PARTICIPANT_STAGE_LABELS = ['completed' => 'Completed'] + self::POLLING_STATUSES;

    /**
     * How the lot is revealed when a participant draws.
     *
     * Both are theatre over a result the server already decided inside a locked
     * transaction — neither picks anything, and neither may show how many lots
     * are left, which the product withholds on purpose.
     */
    public const DRAW_ANIMATIONS = [
        'splitflap' => 'Split-flap board',
        'envelope' => 'Sealed envelope',
    ];

    /** The settings key the reveal is stored under — one choice for every event. */
    public const DRAW_ANIMATION_SETTING = 'draw_animation';

    /**
     * The reveal every event uses, chosen in Setting › Lot Draw. A missing or
     * unknown value falls back to the split-flap board rather than breaking
     * the Confirm page.
     */
    public static function lotReveal(): string
    {
        $chosen = Setting::get(self::DRAW_ANIMATION_SETTING);

        return array_key_exists((string) $chosen, self::DRAW_ANIMATIONS) ? $chosen : 'splitflap';
    }

    public function registrations()
    {
        return $this->hasMany(EventRegistration::class);
    }

    /**
     * The plan's (location, size) pairs, oldest first.
     *
     * The ordering is not cosmetic: the plan form renders these rows in order and
     * the save matches them back on (location, size), so a stable order is what
     * lets a surviving pair keep its id. Without an ORDER BY the database may
     * hand back rows in any order it likes.
     */
    public function plotSizes()
    {
        return $this->hasMany(PlotSize::class)->orderBy('id');
    }

    public function bidangAllocations()
    {
        return $this->hasMany(BidangAllocation::class);
    }

    public function plots()
    {
        return $this->hasMany(Plot::class);
    }

    public function polls()
    {
        return $this->hasMany(Poll::class);
    }

    public function presetAllocations()
    {
        return $this->hasMany(PresetAllocation::class);
    }

    public function joinRequests()
    {
        return $this->hasMany(EventJoinRequest::class);
    }

    /**
     * Called off, as a participant sees it: the draw cancelled, or the event
     * put back to draft. Its requests to join are no longer answered.
     *
     * NOT Unpublished (`closed`). The scheduler unpublishes an event by itself
     * when registration closes (auto_close), while requests sent before the
     * close are still with the office and the draw goes on. Counting that as
     * called off froze those requests and told their companies the event was
     * cancelled.
     */
    public function isCalledOff(): bool
    {
        return $this->polling_status === 'cancelled' || $this->status === 'draft';
    }

    /**
     * Whether a participant may ask to join now: published, inside the
     * registration window, and the draw not started.
     */
    public function acceptsJoinRequests(): bool
    {
        return $this->status === 'open'
            && $this->polling_status === 'draft'
            && now()->between($this->registration_open_at, $this->registration_close_at);
    }

    /**
     * Where a published event stands for a visitor: ended, now, open, register,
     * soon or closed. The public list and the event page both read this, so they never
     * disagree. The event's own dates win over the draw — a fair that is on, or
     * over, is that first — and an open draw whose deadline has passed is
     * closed, even before the scheduler has said so.
     */
    public function publicStage(): string
    {
        return match (true) {
            $this->end_date->isPast() => 'ended',
            $this->start_date->isPast() => 'now',
            $this->polling_status === 'polling' && ! $this->polling_deadline?->isPast() => 'open',
            in_array($this->polling_status, ['polling', 'closed', 'completed', 'cancelled'], true) => 'closed',
            $this->acceptsJoinRequests() => 'register',
            default => 'soon',
        };
    }

    public function getBannerUrlAttribute(): ?string
    {
        return $this->banner ? Storage::url($this->banner) : null;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? 'Draft';
    }

    public function getStatusColourAttribute(): string
    {
        return match ($this->status) {
            'open' => 'mac-status-green',
            'closed' => 'mac-status-orange',
            'completed' => 'mac-status-blue',
            default => 'mac-status-gray',
        };
    }

    /**
     * Static because the lot draw page draws draft → polling → closed →
     * completed as a stepper, so it needs a label for stages the event is not
     * currently in.
     */
    public static function pollingStatusLabel(?string $status): string
    {
        // Anything unrecognised reads as Draft, null included — a draw with no
        // status set has not started.
        return self::POLLING_STATUSES[$status] ?? 'Draft';
    }

    public static function pollingStatusColour(?string $status): string
    {
        return match ($status) {
            'polling' => 'mac-status-green',
            'closed' => 'mac-status-orange',
            'completed' => 'mac-status-blue',
            'cancelled' => 'mac-status-red',
            default => 'mac-status-gray',
        };
    }

    public function getPollingStatusLabelAttribute(): string
    {
        return self::pollingStatusLabel($this->polling_status);
    }

    public function getPollingStatusColourAttribute(): string
    {
        return self::pollingStatusColour($this->polling_status);
    }

    /**
     * Set by the `deleting` hook, read by Auditable's `deleted` hook.
     *
     * A real declared property, not an attribute: Eloquent's __get only runs for
     * undefined properties, so this never reaches the attributes array and never
     * reaches the database. Same reasoning as Company::$auditDeletionCounts.
     *
     * @var array<string, mixed>|null
     */
    public ?array $auditDeletionCounts = null;

    protected static function booted(): void
    {
        static::deleting(function (Event $event) {
            // Nothing refuses an event delete — unlike Company, which throws
            // first — so the count runs unconditionally and the question of
            // ordering it after a guard does not arise. If a refusal is ever
            // added, this line moves below it: counting ahead of a guard that
            // throws does the work and discards it.
            $event->auditDeletionCounts = $event->collectDeletionCounts();
        });
    }

    /**
     * What the cascade is about to take with this event, counted BEFORE the
     * DELETE — the only chance there is, because a row removed by ON DELETE
     * CASCADE fires no Eloquent event and leaves no audit row of its own.
     *
     * The reach was read off the live database, not off the migration: six
     * tables, of which THREE ARE REACHABLE TWO WAYS — `plots`,
     * `bidang_allocations` and `preset_allocations` each carry an `event_id` AND
     * a `plot_size_id`, and both cascade. Counting one column alone under-reports
     * whatever rows carry only the other, so each is counted with an OR across
     * both and the number is rows-destroyed rather than rows-matching-one-key.
     *
     * Every key on every delete, zeros included: a summary whose shape changes
     * with the data cannot be compared against another delete. `links_cleared` is
     * empty here and still present for that reason — nothing survives an event
     * delete having merely lost a reference.
     *
     * Query-builder counts rather than relations, so this holds while the Fasa 3
     * models are still to be written.
     *
     * @return array<string, mixed>
     */
    public function collectDeletionCounts(): array
    {
        $sizeIds = DB::table('plot_sizes')->where('event_id', $this->id)->pluck('id');

        $bothPaths = fn (string $table) => DB::table($table)
            ->where(function ($q) use ($sizeIds) {
                $q->where('event_id', $this->id);

                if ($sizeIds->isNotEmpty()) {
                    $q->orWhereIn('plot_size_id', $sizeIds);
                }
            })
            ->count();

        $mine = fn (string $table) => DB::table($table)->where('event_id', $this->id)->count();

        return [
            'cascade_deleted' => [
                'event_registrations' => $mine('event_registrations'),
                'plot_sizes' => $sizeIds->count(),
                'polls' => $mine('polls'),
                'bidang_allocations' => $bothPaths('bidang_allocations'),
                'plots' => $bothPaths('plots'),
                'preset_allocations' => $bothPaths('preset_allocations'),
            ],

            'links_cleared' => [],
        ];
    }

    /**
     * Auditable reads this when writing the `deleted` row. Null if this model was
     * deleted by a path that bypassed the hook, in which case the row looks
     * exactly as it did before this existed.
     */
    protected function auditDeletionSummary(): ?array
    {
        return $this->auditDeletionCounts;
    }
}
