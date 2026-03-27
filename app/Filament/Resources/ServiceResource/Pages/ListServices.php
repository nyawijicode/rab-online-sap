<?php

namespace App\Filament\Resources\ServiceResource\Pages;

use App\Filament\Resources\ServiceResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Contracts\HasTable;

class ListServices extends ListRecords
{
    protected static string $resource = ServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            Action::make('download_all_xlsx')
                ->label('Download Semua Data (XLSX)')
                ->icon('heroicon-m-arrow-down-tray')
                ->color('success')
                ->url(fn() => route('exports.services.all'))
                ->openUrlInNewTab(false),

            Action::make('download_filtered_xlsx')
                ->label('Download Data Filtered (XLSX)')
                ->icon('heroicon-m-arrow-down-tray')
                ->color('info')
                ->form([]) // supaya Action selalu kirim via POST
                ->action(function (\Livewire\Component&\Filament\Tables\Contracts\HasTable $livewire) {
                    $filters = $livewire->getTableFiltersForm()->getState();
                    $search  = (string) $livewire->getTableSearch();

                    // redirect POST ke route (Livewire)
                    return redirect()->route('exports.services.filtered', [
                        'filters' => json_encode($filters),
                        'search'  => $search,
                    ]);
                })
                ->openUrlInNewTab(false)
                ->tooltip('Download data sesuai filter & pencarian yang aktif'),
        ];
    }
}
