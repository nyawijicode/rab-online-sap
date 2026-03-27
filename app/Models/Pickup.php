<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pickup extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'po_docentry',
        'po_number',
        'vendor_code',
        'vendor_name',
        'pickup_date',
        'pickup_day',
        'pickup_duration',
        'expedition_supplier_code',
        'expedition_supplier_name',
        'notes',
        'status',
        'vendor_address',
        'vendor_pic_name',
        'vendor_pic_phone',
        'no_resi',
        'jangka_waktu_pelaksanaan',
        'tagihan_ke',
        'pengambilan_cabang',
        'tujuan_pengiriman',
        'alamat_dropship',
        'package_id',
        'created_by',
        'updated_by',
        'alamat_ambil',
        'perusahaan_id',
        'kota',
        'attachments',
    ];

    protected $casts = [
        'pickup_date' => 'date',
        'pickup_duration' => 'integer',
        'jangka_waktu_pelaksanaan' => 'date',
        'attachments' => 'array',
    ];
    public function items()
    {
        return $this->hasMany(PickupItem::class);
    }
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }
    protected static function booted()
    {
        static::creating(function ($model) {
            $model->created_by = auth()->id();
        });

        static::updating(function ($model) {
            $model->updated_by = auth()->id();
        });
    }
    public function perusahaan(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'perusahaan_id');
    }
}
