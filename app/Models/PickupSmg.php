<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PickupSmg extends Model
{
    use SoftDeletes;
    protected $table = 'pickup_smgs';

    protected $guarded = [];

    protected $casts = [
        'tanggal_request' => 'date',
        'tanggal_pengambilan' => 'date',
        'personil' => 'array',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    public function perusahaan(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'perusahaan_id');
    }
}
