<?php

namespace App\Filament\Pickup\Resources\PickupSmgResource\Pages;

use App\Filament\Pickup\Resources\PickupSmgResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPickupSmgs extends ListRecords
{
    protected static string $resource = PickupSmgResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
