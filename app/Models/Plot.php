<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plot extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'event_id',
        'plot_size_id',
        'company_id',
        'allocated_to',
        'bidang_id',
        'sub_bidang_id',
        'plot_code',
        'is_allocated',
        'allocation_type',
        'is_locked',
        'allocated_at',
    ];

    protected $casts = [
        'is_allocated' => 'boolean',
        'is_locked' => 'boolean',
        'allocated_at' => 'datetime',
    ];

    /**
     * A plot's readable number: "HALL A-TBP7".
     *
     * Minted wherever plots are built or renumbered, and printed on the slip, in
     * the QR, on every PDF and in every admin listing — so the rule lives once.
     *
     * $token is the craft code where a plot has one, and the plot size where it
     * does not: an uncoded plot reads "HALL A-3X34", which says what it is rather
     * than pretending to a category. The location keeps its spaces.
     */
    public static function codeFor(?string $location, ?string $token, int $n): string
    {
        $place = strtoupper(preg_replace('/[^a-zA-Z0-9 ]/', '', trim($location ?: 'LOC')));
        $place = preg_replace('/\s+/', ' ', $place) ?: 'LOC';

        $tag = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', (string) $token));

        return $place.'-'.($tag ?: 'PLOT').$n;
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function plotSize()
    {
        return $this->belongsTo(PlotSize::class);
    }

    /** The craft CODE row the plot is set aside for, or null for the open pool. */
    public function bidang()
    {
        return $this->belongsTo(KrafBidang::class, 'bidang_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'allocated_to');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
