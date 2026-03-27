<?php

namespace App\Filament\Base\Resources\SapVendorResource\Pages;

use App\Filament\Base\Resources\SapVendorResource;
use Filament\Resources\Pages\ListRecords;

class ListSapVendors extends ListRecords
{
    protected static string $resource = SapVendorResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
