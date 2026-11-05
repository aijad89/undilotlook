<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A product sits in a SUB-CATEGORY, beside its classifications. `bidang_id`
 * still names the category and is kept in step with the sub-category's; a
 * product with no `sub_bidang_id` predates that and is "not placed yet".
 */
class KrafProduk extends Model
{
    use HasFactory;

    protected $table = 'kraf_produks';

    protected $fillable = ['bidang_id', 'sub_bidang_id', 'name'];

    /** `is_active` stays out of $fillable for the reason KrafBidang's does. */
    protected $casts = ['is_active' => 'boolean'];

    public function bidang()
    {
        return $this->belongsTo(KrafBidang::class, 'bidang_id');
    }

    public function subBidang()
    {
        return $this->belongsTo(KrafSubBidang::class, 'sub_bidang_id');
    }
}
