<?php

namespace App\Filament\Pickup\Widgets;

use App\Models\Pickup;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
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

        $todayCount = (clone $baseQuery)->whereDate('created_at', Carbon::today())->count();
        $completedCount = (clone $baseQuery)->where('status', 'completed')->count();
        $totalCount = (clone $baseQuery)->count();

        return [
            Stat::make('Total Pickups', $totalCount)
                ->description('Total keseluruhan jadwal pickup')
                ->descriptionIcon('heroicon-m-truck')
                ->color('primary')
                ->chart([$totalCount - rand(1, 10), $totalCount - rand(1, 5), $totalCount + rand(1, 5), $totalCount]),

            Stat::make('Bulan Ini', (clone $baseQuery)->whereMonth('created_at', Carbon::now()->month)->count())
                ->description('Jumlah pickup bulan ini')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info')
                ->chart([5, 10, 15, 8, 20, 25, 30]),

            Stat::make('Belum Dikirim (Scheduled)', (clone $baseQuery)->where('status', 'scheduled')->count())
                ->description('Menunggu proses pengiriman')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
                
            Stat::make('Selesai (Completed)', $completedCount)
                ->description('Pickup yang telah selesai')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),
        ];
    }
}
