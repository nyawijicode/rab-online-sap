<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LampiranBiayaService extends Model
{
    use SoftDeletes;
    use \App\Traits\HasSystemHistory;
    protected $table = 'lampiran_biaya_services';

    // Laravel otomatis pakai tabel 'lampiran_biaya_services'
    protected $fillable = [
        'pengajuan_id',
        'file_path',
        'original_name',
    ];

    public function pengajuan()
    {
        return $this->belongsTo(Pengajuan::class);
    }
}
