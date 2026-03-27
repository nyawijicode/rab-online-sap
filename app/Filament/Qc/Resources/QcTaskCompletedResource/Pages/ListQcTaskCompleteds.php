<?php

namespace App\Filament\Qc\Resources\QcTaskCompletedResource\Pages;

use App\Filament\Qc\Resources\QcTaskCompletedResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListQcTaskCompleteds extends ListRecords
{
    protected static string $resource = QcTaskCompletedResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action needed
        ];
    }
}
