<?php

namespace App\Exports\Sheets;

use App\Models\RequestTeknisi;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RequestTeknisiReportsSheet implements FromCollection, WithHeadings, WithMapping, WithDrawings, WithStyles, WithTitle
{
    protected Collection $rows;
    protected array $rowCoords = [];
    protected string $title;

    public function __construct(Collection $rows, string $title = 'RequestTeknisi Reports')
    {
        $this->rows  = $rows->values();
        $this->title = $title;
    }

    public function title(): string
    {
        return $this->title;
    }
    public function collection()
    {
        return $this->rows;
    }
    public function headings(): array
    {
        return ['No Request', 'User', 'Foto', 'Keterangan', 'Dibuat'];
    }

    public function map($report): array
    {
        // ambil dari relasi apa pun yang ada
        $req = $report->getRelationValue('requestTeknisi')
            ?? $report->getRelationValue('request');

        $noRequest = $req->no_request
            ?? optional(RequestTeknisi::withTrashed()->find($report->request_teknisi_id))->no_request
            ?? '-';

        $userName  = $report->user->name ?? '-';
        $ket       = (string) ($report->keterangan ?? '-');
        $created   = optional($report->created_at)->format('d/m/Y H:i') ?: '-';

        $rowNumber = count($this->rowCoords) + 2;
        $this->rowCoords[] = [
            'row'  => $rowNumber,
            'path' => $this->resolveImagePath($report->foto),
        ];

        return [$noRequest, $userName, '(lihat gambar)', $ket, $created];
    }

    public function drawings(): array
    {
        $drawings = [];

        foreach ($this->rowCoords as $coord) {
            $path = $coord['path'];
            if (!$path || !file_exists($path)) continue;

            $d = new Drawing();
            $d->setName('Report Photo');
            $d->setDescription('Foto Report');
            $d->setPath($path);

            // KUNCI: ukuran & anchor yang konsisten dengan tinggi baris yg sudah diset di styles()
            $d->setResizeProportional(true);
            $d->setHeight(90);                    // harus <= rowHeight (lihat styles)
            $d->setCoordinates('C' . $coord['row']); // kolom C
            $d->setOffsetX(6);
            $d->setOffsetY(6);

            $drawings[] = $d;
        }

        return $drawings;
    }

    public function styles(Worksheet $sheet)
    {
        // Header tebal + tinggi cukup supaya tidak ketimpa gambar
        $sheet->getRowDimension(1)->setRowHeight(24);

        // Lebar kolom tetap (gambar di C)
        $sheet->getColumnDimension('A')->setWidth(20);
        $sheet->getColumnDimension('B')->setWidth(22);
        $sheet->getColumnDimension('C')->setWidth(24); // area gambar
        $sheet->getColumnDimension('D')->setWidth(40);
        $sheet->getColumnDimension('E')->setWidth(20);

        // Tinggi baris data diset DI SINI (sebelum gambar ditempel)
        foreach ($this->rowCoords as $coord) {
            $sheet->getRowDimension($coord['row'])->setRowHeight(100);
        }

        // Header look
        $sheet->getStyle('A1:E1')->getFont()->setBold(true);
        $sheet->getStyle('A1:E1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE6E6E6');

        // Wrap text untuk keterangan
        $sheet->getStyle('D2:D' . (count($this->rowCoords) + 1))->getAlignment()->setWrapText(true);

        return [];
    }

    protected function resolveImagePath(?string $storedPath): ?string
    {
        if (empty($storedPath)) return null;

        if (Storage::disk('public')->exists($storedPath)) {
            return Storage::disk('public')->path($storedPath);
        }

        $candidate = storage_path('app/public/' . ltrim($storedPath, '/'));
        if (file_exists($candidate)) return $candidate;

        return file_exists($storedPath) ? $storedPath : null;
    }
}
