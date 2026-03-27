<?php

namespace App\Filament\Resources\PengajuanSoPlResource\Pages;

use App\Filament\Resources\PengajuanSoPlResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPengajuanSoPl extends EditRecord
{
    protected static string $resource = PengajuanSoPlResource::class;
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
        ];
    }
}
