<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestTeknisiReport extends Model
{
    use HasFactory;
    protected $table = 'request_teknisi_reports'; // sesuai tabelmu
    protected $fillable = ['request_teknisi_id', 'user_id', 'foto', 'keterangan'];
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    public function request(): BelongsTo
    {
        return $this->belongsTo(RequestTeknisi::class, 'request_teknisi_id');
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    // ⬇⬇⬇ inilah yang hilang ⬇⬇⬇
    public function requestTeknisi()
    {
        return $this->belongsTo(RequestTeknisi::class, 'request_teknisi_id');
    }
}
