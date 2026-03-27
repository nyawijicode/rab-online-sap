<?php

namespace App\Filament\Pajak\Resources\CocokanFakturPajakResource\Pages;

use App\Filament\Pajak\Resources\CocokanFakturPajakResource;
use Filament\Resources\Pages\ManageRecords;

class ManageCocokanFakturPajaks extends ManageRecords
{
    protected static string $resource = CocokanFakturPajakResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
