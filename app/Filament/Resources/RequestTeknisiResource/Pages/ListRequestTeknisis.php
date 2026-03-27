<?php

namespace App\Filament\Resources\RequestTeknisiResource\Pages;

use App\Filament\Resources\RequestTeknisiResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListRequestTeknisis extends ListRecords
{
    protected static string $resource = RequestTeknisiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            // Download semua data (tanpa filter)
            Actions\Action::make('download_all_xlsx')
                ->label('Download Semua (XLSX)')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->url(fn() => route('exports.request_teknisi.all'))
                ->openUrlInNewTab(false)
                ->tooltip('Download seluruh data Request Teknisi dalam format .xlsx'),

            // Download filtered (mengambil state dari Livewire Table)
            Actions\Action::make('download_filtered_xlsx')
                ->label('Download Filtered (XLSX)')
                ->icon('heroicon-o-funnel')
                ->color('info')
                ->url(function ($livewire) {
                    $filters = collect($livewire->getTableFiltersForm()->getState() ?? [])
                        ->filter(fn($v) => $v !== null && $v !== '' && $v !== [])
                        ->all();

                    $search = (string) $livewire->getTableSearch();

                    return route('exports.request_teknisi.filtered', [
                        'filters' => json_encode($filters),
                        'search'  => $search,
                    ]);
                })
                ->openUrlInNewTab(false)
                ->tooltip('Download data sesuai filter & pencarian yang aktif'),
        ];
    }
}
