<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One craft selection a company carries: a CATEGORY and a sub-category under it.
 *
 * `bidang_id` is the `code` IS NULL half of kraf_bidangs, the same half
 * `kraf_sub_bidangs.bidang_id` points at — so the two columns here describe one
 * path down the hierarchy. The craft CODE that the draw matches on is NOT here:
 * it is reached through `kraf_sub_bidangs.kraf_bidang_code_id`, and what the
 * draw actually compares is the string in `event_registrations.code`.
 *
 * Nothing cascades out of this row — it is a leaf — so there is no deletion
 * summary to write, only the trait's ordinary created/updated/deleted rows.
 * Only an admin changes a company's craft selections, and they may change them
 * freely, so that trail is the record of who moved what and when. Every write
 * must go through the model: a mass delete() on the query builder fires no
 * events and logs nothing.
 */
class CompanyKraf extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'company_id',
        'bidang_id',
        'sub_bidang_id',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function bidang()
    {
        return $this->belongsTo(KrafBidang::class, 'bidang_id');
    }

    public function subBidang()
    {
        return $this->belongsTo(KrafSubBidang::class, 'sub_bidang_id');
    }

    /** The classifications ticked under this selection's sub-category. */
    public function pengelasan()
    {
        return $this->belongsToMany(KrafPengelasan::class, 'company_kraf_pengelasan', 'company_kraf_id', 'pengelasan_id')
            ->orderBy('kraf_pengelasan.id');
    }

    /** The products ticked under this selection's sub-category. */
    public function produks()
    {
        return $this->belongsToMany(KrafProduk::class, 'company_kraf_produk', 'company_kraf_id', 'produk_id')
            ->orderBy('kraf_produks.id');
    }
}
