<?php

namespace App\Filament\Resources\RequestMarcommResource\Pages;

use App\Filament\Resources\RequestMarcommResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRequestMarcomms extends ListRecords
{
    protected static string $resource = RequestMarcommResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getTableRecordAction(): ?string
    {
        // Nonaktifkan klik baris = Edit
        return null;
    }
}
