<?php

namespace App\Filament\Pickup\Resources\VendorEkspedisiResource\Pages;

use App\Filament\Pickup\Resources\VendorEkspedisiResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVendorEkspedisi extends EditRecord
{
    protected static string $resource = VendorEkspedisiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
