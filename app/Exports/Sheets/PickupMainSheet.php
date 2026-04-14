<?php

namespace App\Exports\Sheets;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PickupMainSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected Collection $rows;
    protected string $title;

    public function __construct(Collection $rows, string $title = 'Data Pickup')
    {
        $this->rows = $rows->values();
        $this->title = $title;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function collection() { return $this->rows; }

    public function headings(): array
    {
        return [
            'ID', 'Status', 'Perusahaan', 'Dibuat Oleh', 'Diubah Oleh', 
            'PO Number', 'ID Paket', 'Total Qty', 'Vendor', 'Alamat Vendor', 
            'Kota', 'PIC Vendor', 'No HP PIC Vendor', 'Tgl Dibuat', 
            'Tgl Pickup', 'Hari', 'Tgl Pelaksanaan', 'Durasi (Hari)', 
            'Ekspedisi', 'No Resi', 'Barang (Preview)', 'Catatan', 
            'Tagihan Ke', 'Cabang Ambil', 'Tujuan', 'Alamat Dropship', 'Alamat Ambil'
        ];
    }

    private function fmtDate($value, string $format = 'd/m/Y')
    {
        if (empty($value)) return '-';
        if ($value instanceof Carbon) return $value->format($format);
        try { return Carbon::parse($value)->format($format); } catch (\Throwable) { return (string) $value; }
    }

    public function map($p): array
    {
        // 1) Logic Identitas / ID Paket (Prioritas)
        $idPaket = ($p->id_paket ?: $p->package_id) ?: ($p->no_resi ?: $p->po_number);

        // 2) Logic Qty per Unit (Grouping)
        $qtyDetails = $p->items
            ->groupBy('unit')
            ->map(function ($items, $unit) {
                return (int) $items->sum('pickup_quantity') . ' ' . strtoupper($unit ?: 'KOLI');
            })->join(', ');

        // 3) Logic PIC Fallback
        $picName = ($p->cabang_pic_name ?: $p->vendor_pic_name) ?: '-';
        $picPhone = ($p->cabang_pic_phone ?: $p->vendor_pic_phone) ?: '-';

        // 4) Logic Customer / Penerima Fallback
        $kirimKe = ($p->customer_name ?: $p->vendor_name) ?: '-';
        $penerimaName = ($p->penerima_pic_name ?: $p->vendor_pic_name) ?: '-';
        $penerimaPhone = ($p->penerima_pic_phone ?: $p->vendor_pic_phone) ?: '-';

        return [
            $p->id,
            Str::headline($p->status ?? '-'),
            $p->perusahaan->nama_perusahaan ?? '-',
            $p->creator->name ?? '-',
            $p->updater->name ?? '-',
            $p->po_number ?? '-',
            strtoupper($idPaket),
            $qtyDetails ?: '0',
            $p->vendor_name ?? '-',
            $p->vendor_address ?? '-',
            $p->kota ?? '-',
            $picName,
            $picPhone,
            $this->fmtDate($p->created_at, 'd/m/Y H:i'),
            $this->fmtDate($p->pickup_date),
            $p->pickup_day ?? '-',
            $this->fmtDate($p->jangka_waktu_pelaksanaan),
            $p->pickup_duration ?? 0,
            $p->expedition_supplier_name ?? '-',
            $p->no_resi ?? '-',
            $p->items->take(3)->pluck('description')->implode(', '),
            $p->notes ?? '-',
            strtoupper($p->tagihan_ke ?? '-'),
            $p->pengambilan_cabang ?? '-',
            $kirimKe,
            $p->alamat_dropship ?? '-',
            $p->alamat_ambil ?? '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFE6E6E6'],
                ],
            ],
            'A' => ['width' => 18], 'B' => ['width' => 20], 'C' => ['width' => 25],
            'D' => ['width' => 14], 'E' => ['width' => 12], 'F' => ['width' => 25],
            'G' => ['width' => 15], 'H' => ['width' => 20], 'I' => ['width' => 15],
            'J' => ['width' => 20], 'K' => ['width' => 18], 'L' => ['width' => 20],
            'M' => ['width' => 18], 'N' => ['width' => 22], 'O' => ['width' => 25],
            'P' => ['width' => 35], 'Q' => ['width' => 30], 'R' => ['width' => 20],
        ];
    }
}
