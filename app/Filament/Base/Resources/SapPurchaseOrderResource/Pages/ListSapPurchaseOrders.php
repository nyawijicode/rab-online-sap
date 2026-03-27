<?php

namespace App\Filament\Base\Resources\SapPurchaseOrderResource\Pages;

use App\Filament\Base\Resources\SapPurchaseOrderResource;
use Filament\Resources\Pages\ListRecords;

class ListSapPurchaseOrders extends ListRecords
{
    protected static string $resource = SapPurchaseOrderResource::class;

    // Tidak ada create action
    protected function getHeaderActions(): array
    {
        return [];
    }
}
