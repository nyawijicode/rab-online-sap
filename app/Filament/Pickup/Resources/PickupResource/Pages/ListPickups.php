<?php

namespace App\Filament\Pickup\Resources\PickupResource\Pages;

use App\Filament\Pickup\Resources\PickupResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPickups extends ListRecords
{
    protected static string $resource = PickupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
