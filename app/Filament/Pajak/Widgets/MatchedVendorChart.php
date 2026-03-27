<?php

namespace App\Filament\Pajak\Widgets;

use App\Models\CocokanFakturPajak;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class MatchedVendorChart extends ChartWidget
{
    protected static ?string $heading = 'Top 10 Vendor Sudah Cocok';

    protected static ?string $maxHeight = '300px';
    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $data = CocokanFakturPajak::query()
            ->where('status_cocok', true)
            ->select('nama_vendor', DB::raw('count(*) as total'))
            ->groupBy('nama_vendor')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Faktur Sudah Cocok',
                    'data' => $data->pluck('total')->toArray(),
                    'backgroundColor' => '#10b981', // Success (Emerald 500)
                    'borderColor' => '#059669', // Emerald 600
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
