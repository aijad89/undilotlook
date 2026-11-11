<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A participant asking to put their company into an event's draw, with the
 * documents the event asks for. Approving it adds the company to the draw list;
 * the draw list row, not this request, is what the draw reads.
 */
class EventJoinRequest extends Model
{
    use Auditable, HasFactory;

    public const STATUSES = [
        'pending' => 'Waiting for the office',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ];

    public const PAYMENT_METHODS = [
        'cash' => 'Cash',
        'cheque' => 'Cheque',
        'transfer' => 'Online transfer',
        'waiver' => 'Fee waiver',
    ];

    /**
     * Who may join without paying. Every category sends supporting documents
     * that prove it; the office checks them.
     */
    public const WAIVER_CATEGORIES = [
        'disabled' => 'Person with disabilities (OKU), with certificate',
        'award' => 'Anugerah Usahawan Kraf or Anugerah Usahawan Kraf Muda recipient, previous year',
        'first_time' => 'First time joining a promotion with Kraftangan Malaysia',
        'incubator' => 'Incubator entrepreneur, first time joining a promotion with Kraftangan Malaysia',
    ];

    protected $fillable = [
        'event_id',
        'company_id',
        'user_id',
        'payment_method',
        'waiver_category',
        'documents',
        'answers',
        'status',
        'reject_reason',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'documents' => 'array',
        'answers' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
