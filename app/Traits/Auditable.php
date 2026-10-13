<?php

namespace App\Traits;

use App\Models\AuditTrail;

/**
 * Logs create, update and delete to `audit_trails`.
 *
 * WHAT IT DOES NOT RECORD, AND THIS IS NOT A SMALL EXCEPTION.
 * ---------------------------------------------------------------------------
 * The hooks below are Eloquent model events. They fire when PHP deletes a
 * model. They do NOT fire for a delete the database performs itself —
 * `ON DELETE CASCADE` and `ON DELETE SET NULL` change rows inside MySQL without
 * Eloquent ever loading them, so nothing here runs and no audit row is written.
 *
 * Read the trait on a model and the natural conclusion is "this model's
 * deletions are recorded". For two of the models carrying it that conclusion is
 * usually wrong, because a cascade is how their rows usually go:
 *
 *   CompanyKraf        — `company_krafs.company_id` is ON DELETE CASCADE.
 *   EventRegistration  — `event_registrations.company_id` is ON DELETE CASCADE.
 *
 * Delete a company and both are emptied without a trace, along with a dozen
 * other tables. The company's own `deleted` row is written, and reads as though
 * it were the whole event.
 *
 * WHAT CLOSES IT, on the models where the cascade reaches something worth
 * recording: auditDeletionSummary() below, overridden to count the reach BEFORE
 * the DELETE. Six do it as of 2026-09-17 — Company (20 tables), Event (12), User,
 * CompanyStaff, CompanyCertification and CompanyKraf — all in one shape, so the
 * audit trail screen renders any of them without knowing which model it has.
 *
 * TWO THINGS IT STILL DOES NOT COVER, and both are deliberate:
 *
 *   A MASS DELETE fires no model events at all, so `Model::where(...)->delete()`
 *   writes nothing even for a model carrying this trait. PollingController's
 *   plan rewrites are the live case, and they record their own `plan_rewrite` /
 *   `plan_cleared` row against the surviving Event to make up for it.
 *
 *   THE SURVEY TREE is audited by nothing — entry 50 in docs/audits/backlog.md.
 *
 * If you are about to rely on `audit_trails` to answer "what was removed", read
 * that entry and the summaries above before trusting a row's silence.
 */
trait Auditable
{
    /**
     * Fields to exclude from audit logging.
     * Override in your model: protected array $auditExclude = ['password', 'remember_token'];
     */
    public function getAuditExclude(): array
    {
        return $this->auditExclude ?? ['password', 'remember_token'];
    }

    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            $values = collect($model->getAttributes())
                ->except($model->getAuditExclude())
                ->toArray();

            AuditTrail::record('created', $model, null, $values);
        });

        static::updated(function ($model) {
            $dirty = collect($model->getDirty())
                ->except($model->getAuditExclude())
                ->toArray();

            if (empty($dirty)) {
                return;
            }

            $oldValues = collect($model->getOriginal())
                ->only(array_keys($dirty))
                ->toArray();

            AuditTrail::record('updated', $model, $oldValues, $dirty);
        });

        static::deleted(function ($model) {
            $values = collect($model->getAttributes())
                ->except($model->getAuditExclude())
                ->toArray();

            // `new_values` on a delete used to be null, which is where the counts
            // now go for a model that can produce them. See
            // auditDeletionSummary() below and Company::collectDeletionCounts().
            AuditTrail::record('deleted', $model, $values, $model->auditDeletionSummary());
        });
    }

    /**
     * What a cascade took with this model, for the `deleted` row's `new_values`.
     *
     * Null by default: the trait cannot know a model's reach, and most models
     * have none worth recording. A model that CAN say overrides this — see
     * Company::auditDeletionSummary(), which answers office decision 5 by
     * counting all twenty tables a company delete reaches, with `cascade_deleted`
     * and `links_cleared` kept apart so a nulled plot is never reported as
     * destroyed.
     *
     * Five models followed it, and the shape is the point rather than the count:
     * Event, User, CompanyStaff, CompanyCertification and CompanyKraf all return
     * the same two keys, with every key present on every delete including the
     * zeros. That is what lets admin/audit-trails/show.blade.php read a summary
     * without naming a single model.
     *
     * The counting itself CANNOT live in this trait. Trait boot methods run inside
     * Model::boot(), before booted(), so a `deleting` listener registered here
     * fires before a model's own guards — for Company that would mean counting
     * ahead of decision 3's plot_id refusal and discarding the work on every
     * blocked delete. Each model collects in its own `deleting` hook, after its own
     * guards, and hands the result here.
     *
     * @return array<string, mixed>|null
     */
    protected function auditDeletionSummary(): ?array
    {
        return null;
    }

    /**
     * Get all audit trail entries for this model.
     */
    public function auditTrails()
    {
        return $this->morphMany(AuditTrail::class, 'auditable')->latest('created_at');
    }
}
