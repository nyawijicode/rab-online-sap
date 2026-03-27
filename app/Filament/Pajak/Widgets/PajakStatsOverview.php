<?php

namespace App\Filament\Pajak\Widgets;

use App\Models\CocokanFakturPajak;
use App\Models\Sap\SapApInvoice;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PajakStatsOverview extends BaseWidget
{
    protected static ?int $sort = 2;
    protected function getStats(): array
    {
        $now = Carbon::now();
        $weekStart = $now->copy()->startOfWeek(Carbon::MONDAY);
        $weekEnd = $now->copy()->endOfWeek(Carbon::SATURDAY);

        $belumCocokCount = CocokanFakturPajak::where('status_cocok', false)->count();
        $cocokMingguIni = CocokanFakturPajak::where('status_cocok', true)
            ->whereBetween('resolved_at', [$weekStart, $weekEnd])
            ->count();

        $invoiceTanpaFaktur = SapApInvoice::where(fn($q) => $q->whereNull('FakturPajak')->orWhere('FakturPajak', ''))->count();
        $invoiceOpen = SapApInvoice::where('DocStatus', 'O')->count();

        return [
            Stat::make('Total Belum Cocok', $belumCocokCount)
                ->description('Faktur Pajak yang belum ditemukan di SAP atau Coretax')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($belumCocokCount > 0 ? 'danger' : 'success'),
            Stat::make('Cocok Minggu Ini', $cocokMingguIni)
                ->description('Berhasil diselesaikan minggu ini')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
            Stat::make('Invoice Tanpa Faktur', $invoiceTanpaFaktur)
                ->description('A/P Invoice di SAP yang belum memiliki nomor Faktur Pajak')
                ->descriptionIcon('heroicon-m-document-minus')
                ->color($invoiceTanpaFaktur > 0 ? 'warning' : 'success'),
            Stat::make('Invoice Status Open', $invoiceOpen)
                ->description('Jumlah A/P Invoice dengan status Open di SAP')
                ->descriptionIcon('heroicon-m-clock')
                ->color('info'),
        ];
    }
}
