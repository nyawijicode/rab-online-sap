<?php

namespace App\Filament\Resources\PengajuanSoPlResource\Pages;

use App\Filament\Resources\PengajuanSoPlResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPengajuanSoPls extends ListRecords
{
    protected static string $resource = PengajuanSoPlResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
