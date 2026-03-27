<?php

namespace App\Filament\Pajak\Widgets;

use App\Models\CocokanFakturPajak;
use Filament\Widgets\ChartWidget;

class PajakReconciliationStatusChart extends ChartWidget
{
    protected static ?string $heading = 'Status Rekonsiliasi Keseluruhan';

    protected static ?string $maxHeight = '300px';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 1;

    public function getDescription(): ?string
    {
        $sudahCocok = CocokanFakturPajak::where('status_cocok', true)->count();
        $belumCocok = CocokanFakturPajak::where('status_cocok', false)->count();
        $total = $sudahCocok + $belumCocok;

        return "Total Keseluruhan: {$total} Faktur (100%)";
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
            'scales' => [
                'x' => [
                    'display' => false,
                ],
                'y' => [
                    'display' => false,
                ],
            ],
            'maintainAspectRatio' => false,
        ];
    }


    protected function getData(): array
    {
        $sudahCocok = CocokanFakturPajak::where('status_cocok', true)->count();
        $belumCocok = CocokanFakturPajak::where('status_cocok', false)->count();
        $total = $sudahCocok + $belumCocok;

        $persenSudah = $total > 0 ? round(($sudahCocok / $total) * 100, 1) : 0;
        $persenBelum = $total > 0 ? round(($belumCocok / $total) * 100, 1) : 0;

        return [
            'datasets' => [
                [
                    'label' => 'Status Faktur Pajak',
                    'data' => [$sudahCocok, $belumCocok],
                    'backgroundColor' => [
                        '#10b981', // Success (Emerald 500)
                        '#f59e0b', // Amber 500
                    ],
                ],
            ],
            'labels' => [
                "Sudah Cocok ($sudahCocok - $persenSudah%)",
                "Belum Cocok ($belumCocok - $persenBelum%)"
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
