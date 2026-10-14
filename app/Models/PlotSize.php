<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** One (location, size) pair on an event's plan, and how many lots it holds. */
class PlotSize extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'size',
        'quantity',
        'location',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function bidangAllocations()
    {
        return $this->hasMany(BidangAllocation::class);
    }

    public function plots()
    {
        return $this->hasMany(Plot::class);
    }
}
