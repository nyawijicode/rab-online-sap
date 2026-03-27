<?php

namespace App\Exports;

use App\Models\Service;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ServicesFilteredExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected array $filters;
    protected string $search;

    public function __construct(array $filters = [], string $search = '')
    {
        $this->filters = $filters;
        $this->search  = $search;
    }

    public function collection()
    {
        Log::info('ServicesFilteredExport Parameters:', [
            'filters' => $this->filters,
            'search'  => $this->search,
        ]);

        $query = Service::query()
            ->with(['user', 'items']) // butuh items utk mapping & filter
            ->latest('created_at');

        // === Samakan SCOPE dengan tabel (lihat ServiceResource::getEloquentQuery) ===
        $user = auth()->user();
        if ($user && ! $user->hasAnyRole(['servis', 'manager', 'koordinator teknisi', 'superadmin'])) {
            $query->where('user_id', $user->id);
        }

        // === Global search ===
        if (!empty($this->search)) {
            $search = $this->search;
            $query->where(function (Builder $q) use ($search) {
                $q->where('id_paket', 'like', "%{$search}%")
                    ->orWhere('nama_dinas', 'like', "%{$search}%")
                    ->orWhere('kontak', 'like', "%{$search}%")
                    ->orWhere('nomer_so', 'like', "%{$search}%")
                    ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('items', function ($iq) use ($search) {
                        $iq->where('nama_barang', 'like', "%{$search}%")
                            ->orWhere('noserial', 'like', "%{$search}%")
                            ->orWhere('kerusakan', 'like', "%{$search}%");
                    });
            });
        }

        // === Filters dari Filament ===
        // Bentuk tipikal: ['staging' => ['value' => 'request'], 'created_at_range' => ['dari' => '2025-10-01', 'sampai' => '2025-10-29']]
        foreach ($this->filters as $name => $data) {
            // normalisasi
            $value = (is_array($data) && array_key_exists('value', $data))
                ? $data['value']
                : (is_scalar($data) ? $data : null);

            // SELECT 'staging'
            if ($name === 'staging' && $value !== null && $value !== '' && $value !== 'null') {
                $query->where('staging', (string) $value);
            }

            // SELECT 'masih_garansi' (kalau filter ini dipakai kembali di table)
            if ($name === 'masih_garansi' && $value !== null && $value !== '' && $value !== 'null') {
                $query->whereHas('items', fn($iq) => $iq->where('masih_garansi', (string) $value));
            }

            // RANGE 'created_at_range'
            if ($name === 'created_at_range' && is_array($data)) {
                $from = $data['dari']   ?? null;
                $to   = $data['sampai'] ?? null;

                if ($from && $to) {
                    $query->whereBetween('created_at', [
                        \Carbon\Carbon::parse($from)->startOfDay(),
                        \Carbon\Carbon::parse($to)->endOfDay(),
                    ]);
                } elseif ($from) {
                    $query->where('created_at', '>=', \Carbon\Carbon::parse($from)->startOfDay());
                } elseif ($to) {
                    $query->where('created_at', '<=', \Carbon\Carbon::parse($to)->endOfDay());
                }
            }

            // Tambahkan mapping filter lain di sini bila ada di Table()
            // ...
        }

        $results = $query->get();

        Log::info('ServicesFilteredExport Results:', [
            'count'           => $results->count(),
            'filters_applied' => $this->filters,
            'search_applied'  => $this->search,
            'scoped_user_id'  => $user?->id,
        ]);

        return $results;
    }

    public function headings(): array
    {
        return [
            'Nama Pemohon',
            'ID Paket',
            'Nama Dinas',
            'Kontak',
            'No Telepon',
            'Kerusakan',
            'Nama Barang',
            'No Serial',
            'Status Garansi',
            'No SO',
            'Status Staging',
            'Keterangan Staging',
            'Tanggal Dibuat',
            'Tanggal Diupdate',
        ];
    }

    public function map($service): array
    {
        // gabungkan multi item menjadi string baris
        $kerusakan = $service->items->pluck('kerusakan')->filter()->implode("\n");
        $namaBarang = $service->items->pluck('nama_barang')->filter()->implode("\n");
        $noSerial   = $service->items->pluck('noserial')->filter()->implode("\n");
        $garansi    = $service->items->map(fn($i) => $i->masih_garansi === 'Y' ? 'Masih Garansi' : 'Tidak Garansi')->implode("\n");

        return [
            $service->user->name ?? 'Tidak diketahui',
            $service->id_paket,
            $service->nama_dinas,
            $service->kontak,
            $service->no_telepon,
            $kerusakan,
            $namaBarang,
            $noSerial,
            $garansi,
            $service->nomer_so,
            method_exists($service->staging, 'label') ? $service->staging->label() : (string) $service->staging,
            $service->keterangan_staging,
            optional($service->created_at)->format('d/m/Y H:i'),
            optional($service->updated_at)->format('d/m/Y H:i'),
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
            'A' => ['width' => 20],
            'B' => ['width' => 15],
            'C' => ['width' => 20],
            'D' => ['width' => 20],
            'E' => ['width' => 15],
            'F' => ['width' => 30],
            'G' => ['width' => 20],
            'H' => ['width' => 15],
            'I' => ['width' => 15],
            'J' => ['width' => 15],
            'K' => ['width' => 15],
            'L' => ['width' => 30],
            'M' => ['width' => 20],
            'N' => ['width' => 20],
        ];
    }
}
