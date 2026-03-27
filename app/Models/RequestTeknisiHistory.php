<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestTeknisiHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_teknisi_id',
        'user_id',
        'field',
        'old_value',
        'new_value',
        'description',
    ];

    public function requestTeknisi(): BelongsTo
    {
        return $this->belongsTo(RequestTeknisi::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
