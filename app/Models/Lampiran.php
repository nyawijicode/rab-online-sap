<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lampiran extends Model
{
    use SoftDeletes;
    use \App\Traits\HasSystemHistory;

    protected $fillable = [
        'pengajuan_id',
        'lampiran_asset',
        'lampiran_dinas',
        'lampiran_marcomm_kegiatan',
        'lampiran_marcomm_kebutuhan',
        'lampiran_marcomm_promosi',
        'lampiran_biaya_service',

    ];

    protected $casts = [
        'lampiran_asset' => 'boolean',
        'lampiran_dinas' => 'boolean',
        'lampiran_marcomm_kegiatan' => 'boolean',
        'lampiran_marcomm_kebutuhan' => 'boolean',
        'lampiran_marcomm_promosi' => 'boolean',
        'lampiran_biaya_service'     => 'boolean',
    ];

    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(Pengajuan::class);
    }
}
