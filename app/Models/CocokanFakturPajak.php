<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class CocokanFakturPajak extends Model
{
    protected $fillable = [
        'nomor_faktur',
        'nama_vendor',
        'ada_di_coretax',
        'ada_di_sap',
        'status_cocok',
        'first_appeared_at',
        'resolved_at',
        'periode_minggu',
        'periode_bulan',
    ];

    protected $casts = [
        'ada_di_coretax' => 'boolean',
        'ada_di_sap' => 'boolean',
        'status_cocok' => 'boolean',
        'first_appeared_at' => 'date',
        'resolved_at' => 'date',
    ];

    /**
     * Hitung berapa kali faktur ini muncul di data Coretax (PajakImport).
     */
    public function hitungMunculMinggu(): int
    {
        return \App\Models\PajakImport::where('nomor_faktur_pajak', $this->nomor_faktur)->count();
    }

    /**
     * Scope: hanya yang belum cocok
     */
    public function scopeBelumCocok($query)
    {
        return $query->where('status_cocok', false);
    }

    /**
     * Scope: hanya yang sudah cocok
     */
    public function scopeSudahCocok($query)
    {
        return $query->where('status_cocok', true);
    }

    /**
     * Scope: filter berdasarkan periode minggu
     */
    public function scopePeriodeMinggu($query, string $minggu)
    {
        return $query->where('periode_minggu', $minggu);
    }

    /**
     * Scope: filter berdasarkan periode bulan
     */
    public function scopePeriodeBulan($query, string $bulan)
    {
        return $query->where('periode_bulan', $bulan);
    }

    /**
     * Scope: tampilkan record yang relevan untuk periode minggu tertentu.
     * - Yang masih FALSE (belum cocok)
     * - Yang baru saja TRUE (resolved_at di minggu ini)
     */
    public function scopeRelevanUntukMinggu($query, ?string $minggu = null)
    {
        if (!$minggu) {
            $minggu = Carbon::now()->format('o-\\WW');
        }

        // Parse week start and end
        $weekStart = Carbon::now()->setISODate(
            (int) substr($minggu, 0, 4),
            (int) substr($minggu, 6)
        )->startOfWeek(Carbon::MONDAY);
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SATURDAY);

        return $query->where(function ($q) use ($weekStart, $weekEnd) {
            // Masih FALSE
            $q->where('status_cocok', false)
              ->where('first_appeared_at', '<=', $weekEnd);
        })->orWhere(function ($q) use ($weekStart, $weekEnd) {
            // Baru resolved di minggu ini (muncul terakhir kali)
            $q->where('status_cocok', true)
              ->whereBetween('resolved_at', [$weekStart, $weekEnd]);
        });
    }
}
