<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PajakImport extends Model
{
    protected $fillable = [
        'npwp_penjual',
        'nama_penjual',
        'nomor_faktur_pajak',
        'tanggal_faktur_pajak',
        'masa_pajak',
        'tahun',
        'masa_pajak_pengkreditan',
        'tahun_pajak_pengkreditan',
        'status_faktur',
        'harga_jual_dpp',
        'dpp_nilai_lain',
        'ppn',
        'ppnbm',
        'perekam',
        'referensi',
        'nomor_sp2d',
        'valid',
        'dilaporkan',
        'dilaporkan_oleh_penjual',
    ];

    protected $casts = [
        'tanggal_faktur_pajak' => 'date',
        'harga_jual_dpp' => 'decimal:2',
        'dpp_nilai_lain' => 'decimal:2',
        'ppn' => 'decimal:2',
        'ppnbm' => 'decimal:2',
    ];
}
