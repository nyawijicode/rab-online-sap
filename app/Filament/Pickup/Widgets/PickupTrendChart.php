<?php

namespace App\Filament\Pickup\Widgets;

use App\Models\Pickup;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class PickupTrendChart extends ChartWidget
{
    protected static ?string $heading = 'Trend Jadwal Pickup Harian';
    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $data = [];
        $labels = [];

        $user = auth()->user();
        $isPrivileged = $user && $user->hasAnyRole(['superadmin', 'logistik', 'purchasing']);

        $baseQuery = Pickup::query();
        if (!$isPrivileged) {
            $baseQuery->where(function ($q) {
                $q->where('created_by', auth()->id())
                  ->orWhere('cabang_pic_user_id', auth()->id());
            });
        }

        // Get last 7 days
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);

            // Query total on that date
            $count = (clone $baseQuery)->whereDate('pickup_date', $date->toDateString())->count();

            // Add to dataset
            $data[] = $count;
            $labels[] = $date->translatedFormat('d M');
        }

        return [
            'datasets' => [
                [
                    'label' => 'Jadwal Pickup',
                    'data' => $data,
                    'fill' => 'start',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.2)', // amber-500 with opacity
                    'borderColor' => '#f59e0b',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    // Tambahkan opsi supaya Y-axis mulai dari 0
    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0, // supaya angka bulat
                    ],
                ],
            ],
        ];
    }
}
