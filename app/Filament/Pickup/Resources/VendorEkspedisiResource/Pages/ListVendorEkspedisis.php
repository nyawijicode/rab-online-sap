<?php

namespace App\Filament\Pickup\Resources\VendorEkspedisiResource\Pages;

use App\Filament\Pickup\Resources\VendorEkspedisiResource;
use Filament\Resources\Pages\ListRecords;

class ListVendorEkspedisis extends ListRecords
{
    protected static string $resource = VendorEkspedisiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // read-only (tidak ada create)
        ];
    }
}
