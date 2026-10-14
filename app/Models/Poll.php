<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One row per account per event, written the moment a lot changes hands — by
 * LotAllocator and nothing else. "Has this account drawn yet?" is asked here.
 *
 * The size is the plot's, not the poll's: ask the registration's plot.
 */
class Poll extends Model
{
    protected $fillable = [
        'event_id',
        'user_id',
        'code',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
