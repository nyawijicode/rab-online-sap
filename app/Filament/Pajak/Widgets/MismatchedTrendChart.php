<?php

namespace App\Filament\Pajak\Widgets;

use App\Models\CocokanFakturPajak;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class MismatchedTrendChart extends ChartWidget
{
    protected static ?string $heading = 'Tren Faktur Belum Cocok per Minggu';

    protected static ?string $maxHeight = '300px';
    protected static ?int $sort = 6;

    protected function getData(): array
    {
        $data = CocokanFakturPajak::query()
            ->where('status_cocok', false)
            ->select('periode_minggu', DB::raw('count(*) as total'))
            ->groupBy('periode_minggu')
            ->orderBy('periode_minggu')
            ->limit(10)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Belum Cocok',
                    'data' => $data->pluck('total')->toArray(),
                    'fill' => 'start',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'borderColor' => '#f59e0b',
                ],
            ],
            'labels' => $data->pluck('periode_minggu')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
