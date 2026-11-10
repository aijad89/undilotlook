<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** The third level of the craft hierarchy: category → sub-category → this. */
class KrafPengelasan extends Model
{
    use Auditable, HasFactory;

    protected $table = 'kraf_pengelasan';

    protected $fillable = ['sub_bidang_id', 'name'];

    public function subBidang()
    {
        return $this->belongsTo(KrafSubBidang::class, 'sub_bidang_id');
    }
}
