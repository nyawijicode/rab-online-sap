<?php

namespace App\Filament\Resources\PengajuanResource\Widgets;

use App\Models\Pengajuan;
use App\Models\TipeRab;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class TipeRabChart extends ChartWidget
{
    protected static ?string $heading = 'Distribusi Tipe RAB';
    protected static ?int $sort = 3;
    protected static ?string $maxHeight = '250px';

    public ?string $filter = '2026';

    public function getColumnSpan(): int|string|array
    {
        return 1;
    }

    protected function getFilters(): ?array
    {
        return array_combine(
            range(date('Y'), date('Y') - 5),
            range(date('Y'), date('Y') - 5)
        );
    }

    protected function getData(): array
    {
        $year = $this->filter;

        $data = Pengajuan::select(
            'tipe_rabs.nama as tipe_rab_nama',
            DB::raw('count(*) as total')
        )
            ->join('tipe_rabs', DB::raw("SUBSTRING_INDEX(SUBSTRING_INDEX(no_rab, '/', 2), '/', -1)"), '=', 'tipe_rabs.kode')
            ->whereYear('pengajuans.created_at', $year)
            ->whereNull('pengajuans.deleted_at')
            ->groupBy('tipe_rabs.nama')
            ->get();

        $labels = $data->pluck('tipe_rab_nama')->toArray();
        $counts = $data->pluck('total')->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Total Pengajuan',
                    'data' => $counts,
                    'backgroundColor' => [
                        '#38bdf8',
                        '#fbbf24',
                        '#f87171',
                        '#34d399',
                        '#818cf8',
                        '#a78bfa',
                        '#f472b6'
                    ],
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
