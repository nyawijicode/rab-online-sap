<?php

namespace App\Filament\Base\Resources\SapQualityCheckResource\Pages;

use App\Filament\Base\Resources\SapQualityCheckResource;
use Filament\Resources\Pages\ListRecords;

class ListSapQualityChecks extends ListRecords
{
    protected static string $resource = SapQualityCheckResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
