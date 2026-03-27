<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Company extends Model
{
    use HasFactory;
    use \App\Traits\HasSystemHistory;

    protected $fillable = [
        'kode',
        'nama_perusahaan',
        'deskripsi',
        'alamat',
        'telepon',
        'email',
    ];

    // public function services()
    // {
    //     return $this->hasMany(Service::class, 'company_id');
    // }
}
