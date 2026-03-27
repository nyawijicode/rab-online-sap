<?php

namespace App\Filament\Base\Resources\SapProjectResource\Pages;

use App\Filament\Base\Resources\SapProjectResource;
use Filament\Resources\Pages\ListRecords;

class ListSapProjects extends ListRecords
{
    protected static string $resource = SapProjectResource::class;

    // Tidak ada tombol create
    protected function getHeaderActions(): array
    {
        return [];
    }

    // Kalau mau, persistence key bisa di-null biar filter/search nggak ke-cache
    // protected function getTablePersistenceKey(): ?string
    // {
    //     return null;
    // }
}
