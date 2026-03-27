<?php

namespace App\Imports;

use App\Models\PajakImport;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class PajakImportClass implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new PajakImport([
            'npwp_penjual'              => $row['npwp_penjual'] ?? null,
            'nama_penjual'              => $row['nama_penjual'] ?? null,
            'nomor_faktur_pajak'        => $row['nomor_faktur_pajak'] ?? null,
            'tanggal_faktur_pajak'      => isset($row['tanggal_faktur_pajak']) ? $this->parseDate($row['tanggal_faktur_pajak']) : null,
            'masa_pajak'                => $row['masa_pajak'] ?? null,
            'tahun'                     => $row['tahun'] ?? null,
            'masa_pajak_pengkreditan'   => $row['masa_pajak_pengkreditkan'] ?? ($row['masa_pajak_pengkreditan'] ?? null),
            'tahun_pajak_pengkreditan'  => $row['tahun_pajak_pengkreditan'] ?? null,
            'status_faktur'             => $row['status_faktur'] ?? null,
            'harga_jual_dpp'            => $row['harga_jualpenggantian_dpp'] ?? ($row['harga_jualpenggantiandpp'] ?? ($row['harga_jualpenggantian_dpp'] ?? 0)),
            'dpp_nilai_lain'            => $row['dpp_nilai_laindpp'] ?? ($row['dpp_nilai_lain_dpp'] ?? ($row['dpp_nilai_laindpp'] ?? 0)),
            'ppn'                       => $row['ppn'] ?? 0,
            'ppnbm'                     => $row['ppnbm'] ?? 0,
            'perekam'                   => $row['perekam'] ?? null,
            'referensi'                 => $row['referensi'] ?? null,
            'nomor_sp2d'                => $row['nomor_sp2d'] ?? null,
            'valid'                     => $row['valid'] ?? null,
            'dilaporkan'                => $row['dilaporkan'] ?? null,
            'dilaporkan_oleh_penjual'   => $row['dilaporkan_oleh_penjual'] ?? null,
        ]);
    }

    /**
     * Parse date from Excel - handles both serial numbers and string dates.
     */
    private function parseDate($value)
    {
        if (empty($value)) {
            return null;
        }

        // If numeric (Excel serial date)
        if (is_numeric($value)) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value);
            } catch (\Exception $e) {
                return null;
            }
        }

        // Try parsing as string date (d/m/Y or d-m-Y)
        try {
            return \Carbon\Carbon::parse($value);
        } catch (\Exception $e) {
            return null;
        }
    }
}
