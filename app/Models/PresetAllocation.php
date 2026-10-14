<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PresetAllocation extends Model
{
    protected $fillable = [
        'event_id',
        'user_id',
        'bidang_id',
        'sub_bidang_id',
        'plot_size_id',
        'plot_number',
        'plot_size',
        'plot_code',
        'code',
        'priority_rank',
        'is_confirmed',
    ];

    protected $casts = [
        'is_confirmed' => 'boolean',
    ];

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
