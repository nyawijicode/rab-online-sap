<?php

namespace App\Filament\Resources\PengajuanResource\Widgets;

use App\Models\Pengajuan;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class StatusChart extends ChartWidget
{
    protected static ?string $heading = 'Status Pengajuan';
    protected static ?int $sort = 4;
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

        $data = Pengajuan::select('status', DB::raw('count(*) as total'))
            ->whereYear('created_at', $year)
            ->whereNull('deleted_at')
            ->groupBy('status')
            ->get();

        $labels = $data->pluck('status')->map(fn($status) => ucfirst($status))->toArray();
        $counts = $data->pluck('total')->toArray();

        $colors = $data->pluck('status')->map(function ($status) {
            return match (strtolower($status)) {
                'selesai' => '#10b981', // Hijau
                'ditolak', 'tolak' => '#ef4444', // Merah
                'tunggu', 'menunggu', 'pending' => '#f59e0b', // Kuning/Orange
                'expired' => '#818cf8', // Biru
                default => '#6366f1', // Indigo untuk status lain
            };
        })->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Total Pengajuan',
                    'data' => $counts,
                    'backgroundColor' => $colors,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
