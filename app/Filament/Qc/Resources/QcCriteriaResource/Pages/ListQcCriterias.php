<?php

namespace App\Filament\Qc\Resources\QcCriteriaResource\Pages;

use App\Filament\Qc\Resources\QcCriteriaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListQcCriterias extends ListRecords
{
    protected static string $resource = QcCriteriaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
