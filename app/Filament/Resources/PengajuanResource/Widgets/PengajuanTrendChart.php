<?php

namespace App\Filament\Resources\PengajuanResource\Widgets;

use App\Models\Pengajuan;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PengajuanTrendChart extends ChartWidget
{
    protected static ?string $heading = 'Tren Pengajuan Bulanan';
    protected static ?int $sort = 2;
    protected static ?string $maxHeight = '250px';

    public ?string $filter = '2026';

    public function getColumnSpan(): int|string|array
    {
        return 'full';
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
            DB::raw('MONTH(created_at) as month'),
            DB::raw('count(*) as total')
        )
            ->whereYear('created_at', $year)
            ->whereNull('deleted_at')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->pluck('total', 'month')
            ->all();

        $months = [];
        $counts = [];

        for ($m = 1; $m <= 12; $m++) {
            $months[] = Carbon::create()->month($m)->translatedFormat('M');
            $counts[] = $data[$m] ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Pengajuan',
                    'data' => $counts,
                    'fill' => 'start',
                    'borderColor' => '#38bdf8',
                    'backgroundColor' => 'rgba(56, 189, 248, 0.1)',
                ],
            ],
            'labels' => $months,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
