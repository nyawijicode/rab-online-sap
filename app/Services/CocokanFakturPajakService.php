<?php

namespace App\Services;

use App\Models\CocokanFakturPajak;
use App\Models\PajakImport;
use App\Models\Sap\SapApInvoice;
use Carbon\Carbon;

class CocokanFakturPajakService
{
    /**
     * Jalankan pencocokan antara data Coretax (pajak_imports) dan SAP AP Invoice.
     *
     * @return array Statistik hasil pencocokan
     */
    public function jalankanPencocokan(): array
    {
        $now = Carbon::now();
        $periodeMinggu = $now->format('o-\\WW');
        $periodeBulan = $now->format('Y-m');

        // 1. Ambil semua nomor faktur dari Coretax (pajak_imports)
        $coretaxFakturs = PajakImport::whereNotNull('nomor_faktur_pajak')
            ->where('nomor_faktur_pajak', '!=', '')
            ->pluck('nama_penjual', 'nomor_faktur_pajak')
            ->toArray();

        // 2. Ambil semua nomor faktur dari SAP AP Invoice
        $sapFakturs = SapApInvoice::whereNotNull('FakturPajak')
            ->where('FakturPajak', '!=', '')
            ->pluck('CardName', 'FakturPajak')
            ->toArray();

        // 3. Union semua nomor faktur unik
        $semuaNomorFaktur = array_unique(array_merge(
            array_keys($coretaxFakturs),
            array_keys($sapFakturs),
        ));

        $totalTrue = 0;
        $totalFalse = 0;
        $totalBaru = 0;

        foreach ($semuaNomorFaktur as $nomorFaktur) {
            $adaDiCoretax = isset($coretaxFakturs[$nomorFaktur]);
            $adaDiSap = isset($sapFakturs[$nomorFaktur]);
            $statusCocok = $adaDiCoretax && $adaDiSap;

            // Cari nama vendor (prioritas dari Coretax, fallback ke SAP)
            $namaVendor = $coretaxFakturs[$nomorFaktur] ?? $sapFakturs[$nomorFaktur] ?? null;

            // Cek apakah sudah ada record sebelumnya
            $existing = CocokanFakturPajak::where('nomor_faktur', $nomorFaktur)->first();

            if ($existing) {
                // Update record yang sudah ada
                $existing->update([
                    'nama_vendor' => $namaVendor,
                    'ada_di_coretax' => $adaDiCoretax,
                    'ada_di_sap' => $adaDiSap,
                    'status_cocok' => $statusCocok,
                    // Jika sebelumnya FALSE dan sekarang TRUE, set resolved_at
                    'resolved_at' => (!$existing->status_cocok && $statusCocok) ? $now->toDateString() : $existing->resolved_at,
                ]);
            } else {
                // Buat record baru
                CocokanFakturPajak::create([
                    'nomor_faktur' => $nomorFaktur,
                    'nama_vendor' => $namaVendor,
                    'ada_di_coretax' => $adaDiCoretax,
                    'ada_di_sap' => $adaDiSap,
                    'status_cocok' => $statusCocok,
                    'first_appeared_at' => $now->toDateString(),
                    'resolved_at' => $statusCocok ? $now->toDateString() : null,
                    'periode_minggu' => $periodeMinggu,
                    'periode_bulan' => $periodeBulan,
                ]);
                $totalBaru++;
            }

            $statusCocok ? $totalTrue++ : $totalFalse++;
        }

        return [
            'total' => count($semuaNomorFaktur),
            'true' => $totalTrue,
            'false' => $totalFalse,
            'baru' => $totalBaru,
            'periode_minggu' => $periodeMinggu,
            'periode_bulan' => $periodeBulan,
        ];
    }
}
