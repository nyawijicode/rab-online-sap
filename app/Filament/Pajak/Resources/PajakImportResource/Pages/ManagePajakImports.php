<?php

namespace App\Filament\Pajak\Resources\PajakImportResource\Pages;

use App\Filament\Pajak\Resources\PajakImportResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManagePajakImports extends ManageRecords
{
    protected static string $resource = PajakImportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
