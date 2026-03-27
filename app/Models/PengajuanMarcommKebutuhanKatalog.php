<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PengajuanMarcommKebutuhanKatalog extends Model
{
    use HasFactory, SoftDeletes;
    use \App\Traits\HasSystemHistory;

    protected $fillable = [
        'pengajuan_id',
        'cabang',
        'jumlah',
    ];

    /**
     * Relasi ke Pengajuan
     */
    public function pengajuan()
    {
        return $this->belongsTo(Pengajuan::class);
    }
    public function marcommKebutuhan()
    {
        return $this->belongsTo(PengajuanMarcommKebutuhan::class);
    }
}
