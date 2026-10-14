<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A product hangs off the CATEGORY, not off a classification — so the product
 * list a screen offers does not change with whichever third-level row was
 * ticked.
 */
class KrafProduk extends Model
{
    use HasFactory;

    protected $table = 'kraf_produks';

    protected $fillable = ['bidang_id', 'name'];

    /** `is_active` stays out of $fillable for the reason KrafBidang's does. */
    protected $casts = ['is_active' => 'boolean'];

    public function bidang()
    {
        return $this->belongsTo(KrafBidang::class, 'bidang_id');
    }
}
