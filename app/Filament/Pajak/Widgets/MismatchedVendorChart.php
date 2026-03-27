<?php

namespace App\Filament\Pajak\Widgets;

use App\Models\CocokanFakturPajak;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class MismatchedVendorChart extends ChartWidget
{
    protected static ?string $heading = 'Top 10 Vendor Belum Cocok';

    protected static ?string $maxHeight = '300px';
    protected static ?int $sort = 4;

    protected function getData(): array
    {
        $data = CocokanFakturPajak::query()
            ->where('status_cocok', false)
            ->select('nama_vendor', DB::raw('count(*) as total'))
            ->groupBy('nama_vendor')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Faktur Belum Cocok',
                    'data' => $data->pluck('total')->toArray(),
                    'backgroundColor' => '#f59e0b', // Amber 500
                    'borderColor' => '#d97706', // Amber 600
                ],
            ],
            'labels' => $data->pluck('nama_vendor')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
