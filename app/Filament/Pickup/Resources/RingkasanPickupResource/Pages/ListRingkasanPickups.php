<?php

namespace App\Filament\Pickup\Resources\RingkasanPickupResource\Pages;

use App\Filament\Pickup\Resources\RingkasanPickupResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRingkasanPickups extends ListRecords
{
    protected static string $resource = RingkasanPickupResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
