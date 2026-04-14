<?php

namespace App\Filament\Pickup\Widgets;

use App\Models\Pickup;
use Filament\Widgets\ChartWidget;

class PickupStatusChart extends ChartWidget
{
    protected static ?string $heading = 'Distribusi Status Pickup';
    protected static ?int $sort = 2;
    
    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $user = auth()->user();
        $isPrivileged = $user && $user->hasAnyRole(['superadmin', 'logistik', 'purchasing']);

        $baseQuery = Pickup::query();
        if (!$isPrivileged) {
            $baseQuery->where(function ($q) {
                $q->where('created_by', auth()->id())
                  ->orWhere('cabang_pic_user_id', auth()->id());
            });
        }

        $scheduled = (clone $baseQuery)->where('status', 'scheduled')->count();
        $shipped = (clone $baseQuery)->where('status', 'shipped')->count();
        $completed = (clone $baseQuery)->where('status', 'completed')->count();
        $canceled = (clone $baseQuery)->where('status', 'canceled')->count();

        return [
            'datasets' => [
                [
                    'label' => 'Total',
                    'data' => [$scheduled, $shipped, $completed, $canceled],
                    'backgroundColor' => [
                        '#eab308', // warning (scheduled)
                        '#0ea5e9', // info (shipped)
                        '#22c55e', // success (completed)
                        '#ef4444', // danger (canceled)
                    ],
                ],
            ],
            'labels' => ['Scheduled', 'Shipped', 'Completed', 'Canceled'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
