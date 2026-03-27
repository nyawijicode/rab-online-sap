<?php

namespace App\Filament\Pajak\Widgets;

use App\Models\CocokanFakturPajak;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class MatchedTrendChart extends ChartWidget
{
    protected static ?string $heading = 'Tren Faktur Sudah Cocok per Minggu';

    protected static ?string $maxHeight = '300px';
    protected static ?int $sort = 5;

    protected function getData(): array
    {
        $data = CocokanFakturPajak::query()
            ->where('status_cocok', true)
            ->select('periode_minggu', DB::raw('count(*) as total'))
            ->groupBy('periode_minggu')
            ->orderBy('periode_minggu')
            ->limit(10)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Sudah Cocok',
                    'data' => $data->pluck('total')->toArray(),
                    'fill' => 'start',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'borderColor' => '#10b981',
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
