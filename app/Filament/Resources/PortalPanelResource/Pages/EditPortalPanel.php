<?php

namespace App\Filament\Resources\PortalPanelResource\Pages;

use App\Filament\Resources\PortalPanelResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPortalPanel extends EditRecord
{
    protected static string $resource = PortalPanelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
