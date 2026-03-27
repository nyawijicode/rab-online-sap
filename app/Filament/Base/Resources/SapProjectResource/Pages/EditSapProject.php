<?php

namespace App\Filament\Base\Resources\SapProjectResource\Pages;

use App\Filament\Base\Resources\SapProjectResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSapProject extends EditRecord
{
    protected static string $resource = SapProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
