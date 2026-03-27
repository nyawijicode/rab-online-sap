<?php

namespace App\Filament\Pickup\Resources\PickupSmgResource\Pages;

use App\Filament\Pickup\Resources\PickupSmgResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditPickupSmg extends EditRecord
{
    protected static string $resource = PickupSmgResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = Auth::id();
        return $data;
    }
}
