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

class RequestTeknisiMainSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected Collection $rows;
    protected array $requestIds = [];
    protected string $title;

    public function __construct(Collection $rows, string $title = 'Data Request Teknisi')
    {
        $this->rows = $rows->values();
        $this->requestIds = $this->rows->pluck('id')->all();
        $this->title = $title;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function getRequestIds(): array
    {
        return $this->requestIds;
    }

    public function collection() { return $this->rows; }

    public function headings(): array
    {
        return [
            'Nama Pemohon','No Request','ID Paket','Nama Dinas','Nama Kontak','No Telepon',
            'Jenis Pekerjaan','Cabang','Request','Jadwal','Teknisi Ditugaskan','Status',
            'Keterangan','No RAB','Tanggal Dibuat','Tanggal Diupdate',
        ];
    }

    private function fmtDate($value, string $format = 'd/m/Y')
    {
        if (empty($value)) return '-';
        if ($value instanceof Carbon) return $value->format($format);
        // string / DateTimeInterface
        try { return Carbon::parse($value)->format($format); } catch (\Throwable) { return (string) $value; }
    }

    private function fmtDateTime($value, string $format = 'd/m/Y H:i')
    {
        return $this->fmtDate($value, $format);
    }

    public function map($rt): array
    {
        $teknisiPivot = $rt->relationLoaded('teknisis') ? $rt->teknisis->pluck('name')->all() : [];
        $legacy       = $rt->relationLoaded('teknisi') && $rt->teknisi ? [$rt->teknisi->name] : [];
        $teknisiNames = collect($teknisiPivot)->merge($legacy)->unique()->filter()->implode(', ');

        return [
            $rt->user->name ?? '-',
            $rt->no_request ?? '-',
            $rt->id_paket ?? '-',
            $rt->nama_dinas ?? '-',
            $rt->nama_kontak ?? '-',
            $rt->no_telepon ?? '-',
            $rt->jenis_pekerjaan ?? '-',
            $rt->cabang ?? '-',
            $this->fmtDate($rt->tanggal_pelaksanaan), // Request
            $this->fmtDate($rt->tanggal_penjadwalan), // Jadwal
            $teknisiNames ?: '-',
            Str::headline($rt->status ?? '-'),
            $rt->keterangan ?? '-',
            $rt->no_rab ?? '-',
            $this->fmtDateTime($rt->created_at),
            $this->fmtDateTime($rt->updated_at),
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
            'A' => ['width' => 22],'B' => ['width' => 18],'C' => ['width' => 16],
            'D' => ['width' => 22],'E' => ['width' => 20],'F' => ['width' => 16],
            'G' => ['width' => 20],'H' => ['width' => 16],'I' => ['width' => 14],
            'J' => ['width' => 14],'K' => ['width' => 28],'L' => ['width' => 14],
            'M' => ['width' => 30],'N' => ['width' => 18],'O' => ['width' => 20],
            'P' => ['width' => 20],
        ];
    }
}
