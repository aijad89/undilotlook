<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KrafSubBidang extends Model
{
    use HasFactory;

    protected $table = 'kraf_sub_bidangs';

    protected $fillable = ['bidang_id', 'name', 'kraf_bidang_code_id'];

    /** The CATEGORY this sub-category hangs under — the `code` IS NULL half. */
    public function bidang()
    {
        return $this->belongsTo(KrafBidang::class, 'bidang_id');
    }

    /**
     * The coding-sheet row this sub-category maps to, where one has been set.
     *
     * The single place the hierarchy and the craft codes meet, and optional on
     * purpose: setting a company's craft category must never require a code to
     * exist first.
     */
    public function codeBidang()
    {
        return $this->belongsTo(KrafBidang::class, 'kraf_bidang_code_id');
    }

    public function pengelasan()
    {
        return $this->hasMany(KrafPengelasan::class, 'sub_bidang_id');
    }

    public function produks()
    {
        return $this->hasMany(KrafProduk::class, 'sub_bidang_id')->orderBy('id');
    }
}
