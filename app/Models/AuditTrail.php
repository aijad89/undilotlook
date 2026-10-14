<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditTrail extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'performer_type',
        'performer_id',
        'auditable_type',
        'auditable_id',
        'action',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'url',
        'created_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    public function performer()
    {
        return $this->morphTo();
    }

    public function auditable()
    {
        return $this->morphTo();
    }

    /**
     * Get a human-readable label for the auditable model.
     */
    public function getModelLabelAttribute(): string
    {
        if (!$this->auditable_type) {
            return '—';
        }

        return class_basename($this->auditable_type);
    }

    /**
     * Get a human-readable label for the action.
     */
    public function getActionBadgeAttribute(): array
    {
        return match ($this->action) {
            'created'  => ['bg-success', 'Created'],
            'updated'  => ['bg-warning text-dark', 'Updated'],
            'deleted'  => ['bg-danger', 'Deleted'],
            'login'    => ['bg-info', 'Login'],
            'logout'   => ['bg-secondary', 'Logout'],
            // The lot plan's own rows, written by PollingController.
            'plan_rewrite'    => ['bg-warning text-dark', 'Plan rewritten'],
            'plan_cleared'    => ['bg-danger', 'Plan cleared'],
            'plots_generated' => ['bg-info', 'Plots generated'],
            default    => ['bg-dark', ucfirst(str_replace('_', ' ', $this->action))],
        };
    }

    /**
     * Get the changed fields summary.
     */
    public function getChangedFieldsAttribute(): array
    {
        // A DELETE HAS NO FIELD CHANGES, and since 2026-09-03 it can have
        // `new_values`: office decision 5 puts a cascade count summary there
        // (see Company::collectDeletionCounts()). Without this guard the loop
        // below reads that summary as three changed fields called
        // `cascade_deleted`, `links_cleared` and `owner_deleted` — and the show
        // screen, which branches on this being empty, would stop displaying the
        // deleted record at all. Returning [] restores exactly the behaviour
        // that held while `new_values` was always null on a delete.
        //
        // THE TEST IS THE SHAPE, NOT THE ACTION, since 2026-09-17. It was
        // `action === 'deleted'`, which was the whole set on the day it was
        // written and is not any more: PollingController records `plan_rewrite`
        // and `plan_cleared` against an Event that SURVIVES, carrying the same
        // summary. Those are not deletes of their subject, so they cannot be
        // caught by naming actions — and one more action name would only move the
        // same bug to the next caller. A summary is whatever carries
        // `cascade_deleted`; that is what this now asks.
        if ($this->action === 'deleted' || isset($this->new_values['cascade_deleted'])) {
            return [];
        }

        if (!$this->old_values && !$this->new_values) {
            return [];
        }

        $fields = [];
        $new = $this->new_values ?? [];
        $old = $this->old_values ?? [];

        foreach ($new as $key => $value) {
            $fields[$key] = [
                'old' => $old[$key] ?? null,
                'new' => $value,
            ];
        }

        return $fields;
    }

    /**
     * Record an audit entry.
     */
    public static function record(
        string $action,
        ?Model $auditable = null,
        ?array $oldValues = null,
        ?array $newValues = null,
    ): self {
        $performer = null;
        $performerType = null;
        $performerId = null;

        if (auth()->guard('admin')->check()) {
            $performer = auth()->guard('admin')->user();
            $performerType = get_class($performer);
            $performerId = $performer->id;
        } elseif (auth()->check()) {
            $performer = auth()->user();
            $performerType = get_class($performer);
            $performerId = $performer->id;
        }

        return static::create([
            'performer_type' => $performerType,
            'performer_id'   => $performerId,
            'auditable_type' => $auditable ? get_class($auditable) : null,
            'auditable_id'   => $auditable?->getKey(),
            'action'         => $action,
            'old_values'     => $oldValues,
            'new_values'     => $newValues,
            'ip_address'     => request()->ip(),
            'user_agent'     => request()->userAgent(),
            'url'            => request()->fullUrl(),
            'created_at'     => now(),
        ]);
    }
}
