<?php

namespace App\Filament\Resources\PengajuanSoPlResource\Pages;

use App\Filament\Resources\PengajuanSoPlResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePengajuanSoPl extends CreateRecord
{
    protected static string $resource = PengajuanSoPlResource::class;
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
