<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PickupItem extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'pickup_id',
        'line_num',
        'item_code',
        'description',
        'po_quantity',
        'pickup_quantity',
    ];

    protected $casts = [
        'po_quantity' => 'decimal:6',
        'pickup_quantity' => 'decimal:6',
    ];

    protected $appends = [
        'item_code_sap',
        'item_code_manual',
    ];

    public function getItemCodeSapAttribute()
    {
        return $this->item_code;
    }

    public function getItemCodeManualAttribute()
    {
        return $this->item_code;
    }

    public function pickup()
    {
        return $this->belongsTo(Pickup::class);
    }
}
