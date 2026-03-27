<?php

namespace App\Filament\Resources\PengajuanMarcommKebutuhanKatalogResource\Pages;

use App\Filament\Resources\PengajuanMarcommKebutuhanKatalogResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPengajuanMarcommKebutuhanKatalog extends EditRecord
{
    protected static string $resource = PengajuanMarcommKebutuhanKatalogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
