<?php

namespace App\Models;

use App\Traits\Auditable;
use Endroid\QrCode\Builder\Builder as QrBuilder;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use Auditable, HasFactory, Notifiable;

    protected array $auditExclude = ['password', 'remember_token'];

    /** @var list<string> */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'ic_number',
        'community_id',
        'is_active',
    ];

    /** @var list<string> */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function company()
    {
        return $this->hasOne(Company::class);
    }

    /**
     * The registrations this account may act on — read THROUGH the company.
     *
     * NOT `hasMany(EventRegistration::class)` on `event_registrations.user_id`.
     * Ownership is one predicate, `company_id`: that column is NOT NULL and
     * foreign-keyed, while `user_id` is nullable and nothing in the schema stops
     * a null arriving. Asking the question two ways in two files is how the two
     * answers drift.
     *
     * Routing the relation rather than writing the join at each call site is
     * what keeps it one definition. It also answers the awkward case
     * structurally: an account with no company traverses to an empty set, so
     * `firstOrFail()` 404s and `get()` returns nothing — no caller needs a null
     * branch to say so.
     *
     * NOTE for anyone reading a row from here: HasManyThrough selects the
     * through key as `laravel_through_key`, so a model fetched this way carries
     * one attribute the table does not have.
     */
    public function registrations()
    {
        return $this->hasManyThrough(EventRegistration::class, Company::class);
    }

    /**
     * Set by booted()'s `deleting` hook, read by Auditable's `deleted` hook.
     *
     * A real declared property, not an attribute — Eloquent's __get only runs
     * for undefined properties, so this never reaches the attributes array and
     * never reaches the database.
     *
     * @var array<string, mixed>|null
     */
    public ?array $auditDeletionCounts = null;

    protected static function booted(): void
    {
        static::deleting(function (User $user) {
            if ($user->company()->exists()) {
                throw ValidationException::withMessages([
                    'user' => 'Cannot delete user while a company is still associated. Delete the company first.',
                ]);
            }

            // AFTER THE GUARD, and before the DELETE. Counting first would run
            // the queries and throw them away on every refused delete, and a
            // refusal must not pay for a summary nobody will read.
            $user->auditDeletionCounts = $user->collectDeletionCounts();
        });
    }

    /**
     * What a cascade is about to take with this account, counted BEFORE the delete.
     *
     * Without this, deleting an account writes one `User/deleted` audit row with
     * `new_values` null while the database empties three tables underneath it.
     * The trait's hooks are Eloquent model events, and a row removed by
     * `ON DELETE CASCADE` fires none of them — so the only chance to record what
     * went is before the statement runs.
     *
     * EVERY KEY ON EVERY DELETE, zeros included. A summary whose shape changes
     * with the data cannot be compared between two deletes.
     *
     * `cascade_deleted` and `links_cleared` are kept apart on purpose: a plot
     * that merely stops naming who drew it is not a destroyed plot, and
     * reporting it as one would be worse than recording nothing.
     *
     * WHAT THE GUARD ABOVE MEANS FOR THESE NUMBERS: `companies.user_id` is
     * RESTRICT and the hook refuses outright, so anything reaching this line is
     * a COMPANY-LESS account. `event_registrations`, `polls` and
     * `preset_allocations` are written by flows that require a company, so in
     * practice they are zero. They are counted because the foreign key cascades
     * whether or not today's data uses it.
     *
     * COUNTED ON THE COLUMN, NOT THROUGH `registrations()`. That relation reads
     * through `companies.user_id`, so it returns nothing for exactly the
     * accounts that reach this line — while `event_registrations.user_id` is
     * what actually cascades. Two different questions, and this is the one the
     * database is about to ask.
     *
     * @return array<string, mixed>
     */
    public function collectDeletionCounts(): array
    {
        $mine = fn (string $table) => DB::table($table)->where('user_id', $this->id)->count();

        return [
            'cascade_deleted' => [
                'event_registrations' => $mine('event_registrations'),
                'polls' => $mine('polls'),
                'preset_allocations' => $mine('preset_allocations'),
            ],

            'links_cleared' => [
                'plots.allocated_to' => DB::table('plots')->where('allocated_to', $this->id)->count(),
            ],
        ];
    }

    /**
     * Auditable reads this when writing the `deleted` row. Null if this model was
     * deleted by some path that bypassed the hook, in which case the row looks
     * exactly as it did before this existed.
     */
    protected function auditDeletionSummary(): ?array
    {
        return $this->auditDeletionCounts;
    }

    /* ── Identity code ────────────────────────────────────────────────────
     |
     | One code per participant, scanned at the counter to pull up who is
     | standing there. It lives on the model and not in a controller because the
     | printed slip and the on-screen copy have to be the same code — two places
     | generating it is two places for it to drift.
     |
     | It is md5 of the IC, which was the instruction. What that buys, plainly:
     |
     |   A Malaysian IC is twelve digits. The whole keyspace is 10^12 and md5 is
     |   fast, so a code read off a slip can be walked back to the IC it came
     |   from in minutes on ordinary hardware. This is an IDENTIFIER, NOT A
     |   SECRET, and it must not be relied on to hide anything.
     |
     | If a code ever needs to withstand that, the change is
     | hash_hmac('sha256', $ic, config('app.key')): a secret in the hash is what
     | makes the keyspace unwalkable, not a different algorithm. Everything reads
     | through here, so it is one edit.
     */
    public function icHash(): ?string
    {
        return $this->ic_number ? md5($this->ic_number) : null;
    }

    /** The same code as a scannable PNG data URI, or null when no IC is on file. */
    public function icQrDataUri(int $size = 240): ?string
    {
        $hash = $this->icHash();

        if (! $hash) {
            return null;
        }

        return (new QrBuilder(
            writer: new PngWriter(),
            data: $hash,
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: $size,
            margin: 8,
        ))->build()->getDataUri();
    }
}
