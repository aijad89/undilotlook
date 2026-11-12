<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One company's place in one event's draw.
 *
 * THE EXISTENCE OF THE ROW IS THE ZONE'S APPROVAL; `verified_at` IS HQ'S. A
 * zone admin puts a company into the list with a craft code and a booth size,
 * HQ checks it again, and only then does the participant hear of it or the
 * draw open (PollingGuard). The system this replaces
 * carried a seven-value status and the screens around it; the draw read none of
 * it.
 *
 * TWO KEYS, TWO QUESTIONS. `company_id` is ownership — NOT NULL, foreign-keyed,
 * and what every "whose is this" question asks. `user_id` is the ACCOUNT that
 * draws: `polls`, `plots.allocated_to` and the preset allocations all key on it,
 * and it is nullable. Asking the wrong one of the two is how a company with no
 * account yet disappears off a screen that should list it.
 *
 * `code` is a STRING, not a reference. It holds a craft code from the coding
 * sheet (TBPS, RKP) and is matched against `kraf_bidangs.code` on the rows plots
 * are categorised by. That string-against-key mismatch is deliberate: it is what
 * the allocator compares, and changing it means rewriting the allocator.
 */
class EventRegistration extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'event_id',
        'company_id',
        'user_id',
        'code',
        'code_updated_at',
        'plot_size',
        'plot_id',
        'confirmed_participation',
        'confirmed_at',
        'verified_by',
        'verified_at',
        'first_verified_at',
        'hq_note',
        'payment_note',
    ];

    /**
     * `reminded_at` is deliberately out of $fillable: it is stamped when the
     * close reminder is QUEUED, by the command that queues it and by nothing
     * else. The delivery record lives in `notifications`.
     */
    protected $casts = [
        'code_updated_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'reminded_at' => 'datetime',
        'verified_at' => 'datetime',
        'first_verified_at' => 'datetime',
        'confirmed_participation' => 'boolean',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /** The account that draws. Null until the company has one — see the docblock. */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plot()
    {
        return $this->belongsTo(Plot::class);
    }

    /**
     * Still owed a lot: no plot at all, or a lot set aside that was never taken
     * up. A set-aside lot carries `plot_id` from the moment it is reserved, but
     * the company only holds it once `polls` records the hand-over — until then
     * its lot page and slip stay shut. The bulk run, "completed" and the
     * dashboard all ask this one question.
     */
    public function scopeAwaitingLot($query)
    {
        return $query->where(fn ($q) => $q
            ->whereNull('plot_id')
            ->orWhereNotExists(fn ($p) => $p->selectRaw('1')->from('polls')
                ->whereColumn('polls.user_id', 'event_registrations.user_id')
                ->whereColumn('polls.event_id', 'event_registrations.event_id')));
    }

    /**
     * A ZONE ADMIN'S CHANGE UNDOES HQ'S CHECK — to the code, the size, or the
     * payment note HQ verified against. Here rather than in each door,
     * because the code and size are written from four of them — the row, the
     * bulk form, and the company page through Company::syncBoothSize(). A change
     * by HQ keeps whatever verification HQ itself is setting.
     */
    protected static function booted(): void
    {
        static::updating(function (self $registration) {
            $admin = auth('admin')->user();

            if ($admin instanceof Admin && ! $admin->hasGlobalAccess() && $registration->isDirty(['code', 'plot_size', 'payment_note'])) {
                $registration->verified_at = null;
            }
        });
    }

    /**
     * The participant has been told it is in: HQ verified it once. Stays so
     * while a zone edit waits for HQ again — a company already told it is in
     * does not drop out of its own portal because the office is re-checking.
     */
    public function scopeAnnounced($query)
    {
        return $query->whereNotNull('event_registrations.first_verified_at');
    }

    public function verifier()
    {
        return $this->belongsTo(Admin::class, 'verified_by');
    }
}
