<?php

namespace App\Filament\Pickup\Resources\PickupSmgResource\Pages;

use App\Filament\Pickup\Resources\PickupSmgResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreatePickupSmg extends CreateRecord
{
    protected static string $resource = PickupSmgResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();
        return $data;
    }
}
