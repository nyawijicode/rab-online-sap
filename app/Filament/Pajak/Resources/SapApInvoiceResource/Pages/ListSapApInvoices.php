<?php

namespace App\Filament\Pajak\Resources\SapApInvoiceResource\Pages;

use App\Filament\Pajak\Resources\SapApInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSapApInvoices extends ListRecords
{
    protected static string $resource = SapApInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
