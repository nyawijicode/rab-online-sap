<?php

namespace App\Filament\Resources\RequestTeknisiResource\Pages;

use App\Filament\Resources\RequestTeknisiResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRequestTeknisi extends CreateRecord
{
    protected static string $resource = RequestTeknisiResource::class;
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        // User biasa TIDAK boleh set status selain 'request'
        if (! $user->hasAnyRole(['superadmin', 'koordinator teknisi'])) {
            $data['status'] = 'request';
        }
        // kalau bukan superadmin/koordinator, abaikan payload reports saat create
        if (! auth()->user()->hasAnyRole(['superadmin', 'koordinator teknisi'])) {
            unset($data['reports']);
        }
        if (empty($data['closing'])) {
            $data['id_paket'] = 'Belum Closing';
        }
        // pastikan nama_dinas terisi minimal 'Belum Closing'
        $data['nama_dinas'] = $data['nama_dinas'] ?? 'Belum Closing';
        return $data;
    }
}
