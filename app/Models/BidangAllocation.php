<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** How many lots of one plan row are set aside for one craft code. */
class BidangAllocation extends Model
{
    protected $fillable = [
        'event_id',
        'bidang_id',
        'sub_bidang_id',
        'plot_size_id',
        'plots_count',
    ];

    /**
     * This allocation's share of the whole draw, worked out when asked. A stored
     * column would be a third number that has to keep agreeing with plots_count
     * and the event's total.
     */
    public function getSharePercentAttribute(): float
    {
        $total = $this->event?->total_plots ?: 0;

        return $total > 0 ? round(($this->plots_count / $total) * 100, 2) : 0.0;
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function bidang()
    {
        return $this->belongsTo(KrafBidang::class, 'bidang_id');
    }

    public function plotSize()
    {
        return $this->belongsTo(PlotSize::class);
    }
}
