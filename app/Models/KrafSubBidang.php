<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KrafSubBidang extends Model
{
    use HasFactory;

    protected $table = 'kraf_sub_bidangs';

    protected $fillable = ['bidang_id', 'name'];

    /** The CATEGORY this sub-category hangs under — the `code` IS NULL half. */
    public function bidang()
    {
        return $this->belongsTo(KrafBidang::class, 'bidang_id');
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
