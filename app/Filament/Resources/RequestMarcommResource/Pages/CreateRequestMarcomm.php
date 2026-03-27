<?php

namespace App\Filament\Resources\RequestMarcommResource\Pages;

use App\Filament\Resources\RequestMarcommResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRequestMarcomm extends CreateRecord
{
    protected static string $resource = RequestMarcommResource::class;
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // pastikan user_id terisi (backup kalau hidden tidak ter-dehydrate)
        if (empty($data['user_id'])) {
            $data['user_id'] = auth()->id();
        }

        return $data;
    }
}
