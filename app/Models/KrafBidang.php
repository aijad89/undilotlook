<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * ONE TABLE, TWO LISTS. `code` IS NULL is a craft CATEGORY (TEKSTIL, HASIL
 * RIMBA, …), the top of the hierarchy a company picks from. `code` IS NOT NULL
 * is a craft CODE (TBPS, RKP, …) from the printed coding sheet, which a plot is
 * categorised by and a draw entry is matched against.
 *
 * Every query here must say which half it means — scopeCategories() and
 * scopeCodes() below exist so that saying it is one word. A listing that forgets
 * shows both lists at once; a delete that forgets removes a row from the list
 * nobody was looking at.
 *
 * The two halves meet at `kraf_sub_bidangs.kraf_bidang_code_id`, which is
 * optional, and nowhere else.
 */
class KrafBidang extends Model
{
    use HasFactory;

    protected $table = 'kraf_bidangs';

    protected $fillable = [
        'bil',
        'name',
        'pecahan_bidang',
        'pecahan_sub_bidang',
        'code',
        'allow_multiple_sub_bidang',
    ];

    /**
     * `is_active` is deliberately NOT fillable: retiring a code is a decision
     * with consequences for every record carrying it, so it moves through an
     * explicit toggle and the seeder, never through a form's mass assignment.
     */
    protected $casts = [
        'is_active' => 'boolean',
        'allow_multiple_sub_bidang' => 'boolean',
    ];

    /** The craft hierarchy — what a company picks its category from. */
    public function scopeCategories($query)
    {
        return $query->whereNull('code');
    }

    /** The coding sheet — what a draw entry's `code` is matched against. */
    public function scopeCodes($query)
    {
        return $query->whereNotNull('code')->where('code', '!=', '');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function subBidangs()
    {
        return $this->hasMany(KrafSubBidang::class, 'bidang_id');
    }

    public function produks()
    {
        return $this->hasMany(KrafProduk::class, 'bidang_id');
    }
}
