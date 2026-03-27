<?php

namespace App\Filament\Qc\Resources\MonitoringQcResource\Pages;

use App\Filament\Qc\Resources\MonitoringQcResource;
use Filament\Resources\Pages\ListRecords;

class ListMonitoringQcs extends ListRecords
{
    protected static string $resource = MonitoringQcResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('refresh')
                ->label('Refresh Data')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->action(function () {
                    \App\Models\Sap\MonitoringQc::syncData();
                    \Filament\Notifications\Notification::make()
                        ->title('Data Monitoring Berhasil diperbarui')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function mount(): void
    {
        parent::mount();

        // Initial sync if table is empty
        if (\App\Models\Sap\MonitoringQc::count() === 0) {
            \App\Models\Sap\MonitoringQc::syncData();
        }
    }
}
