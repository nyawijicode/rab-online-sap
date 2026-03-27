<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PortalPanel extends Model
{
    use HasFactory;
    use \App\Traits\HasSystemHistory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'url',
        'badge',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Scope untuk panel aktif
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
