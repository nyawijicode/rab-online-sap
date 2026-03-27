<?php

namespace App\Filament\Pickup\Resources\PickupResource\Pages;

use App\Filament\Pickup\Resources\PickupResource;
use App\Models\Company;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreatePickup extends CreateRecord
{
    protected static string $resource = PickupResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();

        return $data;
    }
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
