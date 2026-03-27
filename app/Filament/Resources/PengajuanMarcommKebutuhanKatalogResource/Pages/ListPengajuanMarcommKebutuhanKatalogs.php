<?php

namespace App\Filament\Resources\PengajuanMarcommKebutuhanKatalogResource\Pages;

use App\Filament\Resources\PengajuanMarcommKebutuhanKatalogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPengajuanMarcommKebutuhanKatalogs extends ListRecords
{
    protected static string $resource = PengajuanMarcommKebutuhanKatalogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
