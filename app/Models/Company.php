<?php

namespace App\Models;

use App\Support\EntrepreneurCategories;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * ONE USER OWNS EXACTLY ONE COMPANY. `User::company()` is a hasOne and
 * `companies.user_id` carries a unique index, so this is a schema promise rather
 * than a convention. If you find yourself wanting one user to own two, that is a
 * product decision that changes the relation, the admin screens and a migration.
 *
 * THERE IS NO ADDRESS HERE. `state` is the OFFICE ZONE a company is administered
 * under — what a state admin is scoped to — not geography. The draw needs to know
 * which zone a company belongs to, not where its workshop is.
 */
class Company extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'state',
        'entrepreneur_category',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * The draw lists this company is on.
     *
     * NAMED `eventRegistrations()`, NOT `registrations()`. A relation called by
     * the wrong name is not an error on a model — Eloquent resolves `$company->
     * registrations` as a missing attribute, which under
     * `preventAccessingMissingAttributes()` throws in tests and, in production,
     * would simply read as nothing. `User::registrations()` is the hasManyThrough
     * that reaches these rows from the other side.
     */
    public function eventRegistrations()
    {
        return $this->hasMany(EventRegistration::class);
    }

    /**
     * The craft categories this company carries, primary first.
     *
     * Each row is a (category, sub-category) pair from the craft hierarchy — the
     * `code` IS NULL half of `kraf_bidangs`. The craft CODE that a draw matches
     * on is not here; it is `event_registrations.code`, set per event.
     */
    public function krafSelections()
    {
        return $this->hasMany(CompanyKraf::class)->orderByDesc('is_primary')->orderBy('id');
    }

    /**
     * A government agency or similar, rather than a craft entrepreneur.
     *
     * This is what lets a plot be preset for a body that belongs to no craft
     * category at all — the case that ALWAYS crosses categories, and the one the
     * draw has to be asked about rather than silently allowed.
     */
    public function isExternalEntity(): bool
    {
        return in_array($this->entrepreneur_category, EntrepreneurCategories::EXTERNAL_ENTITY, true);
    }

    /**
     * Set by booted()'s `deleting` hook, read by Auditable's `deleted` hook.
     *
     * A real declared property, not an attribute: Eloquent's __get only runs for
     * undefined properties, so this never reaches the attributes array and never
     * reaches the database.
     *
     * @var array<string, mixed>|null
     */
    public ?array $auditDeletionCounts = null;

    /**
     * A COMPANY THAT HOLDS A LOT CANNOT BE DELETED.
     *
     * Without this the delete reaches the database, `event_registrations`
     * cascades, and the draw result disappears — a foreign-key error at best,
     * and one that cannot explain itself to the admin. Being below Eloquent it
     * would write nothing to `audit_trails` either.
     *
     * WHY `plot_id` AND NOT `confirmed_participation`. Both answer "did this
     * company get a lot", and in practice they agree. That agreement is luck.
     * `plot_id` is the foreign key to the lot itself: it is set by the thing that
     * hands out lots and by nothing else, and it sits on the same row as
     * `company_id`, so the question is one join rather than two.
     *
     * `polls` is a third trace and deliberately NOT the test: it records a vote
     * cast, and a company can vote and be allocated nothing.
     *
     * THE ORDER OF THE TWO STATEMENTS IS DELIBERATE. Counting first would run
     * the summary queries and throw them away on every refused delete, and a
     * refusal must not pay for a summary nobody will read.
     */
    protected static function booted(): void
    {
        static::deleting(function (Company $company) {
            $holdsALot = DB::table('event_registrations')
                ->where('company_id', $company->id)
                ->whereNotNull('plot_id')
                ->exists();

            if ($holdsALot) {
                throw ValidationException::withMessages([
                    'company' => 'Cannot delete this company: it holds a lot in a draw that has already run. '
                        .'Withdraw the lot first.',
                ]);
            }

            $company->auditDeletionCounts = $company->collectDeletionCounts();
        });
    }

    /**
     * What a cascade is about to take with this company, counted BEFORE the delete.
     *
     * The trait's hooks are Eloquent model events, and a row removed by
     * `ON DELETE CASCADE` fires none of them — so this is the only chance to
     * record what went.
     *
     * EVERY KEY ON EVERY DELETE, zeros included: a summary whose shape changes
     * with the data cannot be compared between two deletes.
     *
     * `cascade_deleted` and `links_cleared` are kept apart because a plot that
     * merely stops naming its occupant is not a destroyed plot, and reporting it
     * as one would be worse than recording nothing.
     *
     * @return array<string, mixed>
     */
    public function collectDeletionCounts(): array
    {
        $mine = fn (string $table) => DB::table($table)->where('company_id', $this->id)->count();

        return [
            'cascade_deleted' => [
                'event_registrations' => $mine('event_registrations'),
                'company_krafs' => $mine('company_krafs'),
            ],

            'links_cleared' => [
                'plots.company_id' => $mine('plots'),
            ],
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
