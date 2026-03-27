<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PengajuanSoPl extends Model
{
    use HasFactory;
    use \App\Traits\HasSystemHistory;
    use SoftDeletes;

    /**
     * Nama tabel (opsional, Laravel otomatis pakai 'pengajuan_so_pls').
     */
    protected $table = 'pengajuan_so_pls';

    /**
     * Kolom yang boleh di-mass-assign.
     */
    protected $fillable = [
        'user_id',
        'nama_dinas',
        'upload_file_rab',
        'upload_file_sp',
        'nama_pic',
        'nomor_pic',
        'upload_file_npwp',
        'alamat_pengiriman',
        'keterangan',
        'no_so_pl',
        'tanggal_respon',
        'status',
    ];

    /**
     * Casting atribut.
     */
    protected $casts = [
        'tanggal_respon' => 'datetime',
    ];

    protected static function booted()
    {
        static::saving(function (self $model) {
            // Default status kalau kosong
            if (empty($model->status)) {
                $model->status = 'pending';
            }

            // Hanya kalau tanggal_respon masih NULL
            if (empty($model->tanggal_respon)) {
                // Kalau No SO diisi (dari kosong ke ada nilai)
                if (! empty($model->no_so_pl)) {
                    $model->tanggal_respon = now();

                    // Kalau sebelumnya status masih pending, naik jadi proses
                    if ($model->status === 'pending') {
                        $model->status = 'proses';
                    }
                }
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
