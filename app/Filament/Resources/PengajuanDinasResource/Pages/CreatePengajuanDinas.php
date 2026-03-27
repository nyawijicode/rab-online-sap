<?php

namespace App\Filament\Resources\PengajuanDinasResource\Pages;

use App\Filament\Resources\PengajuanDinasResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePengajuanDinas extends CreateRecord
{
    protected static string $resource = PengajuanDinasResource::class;
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
    protected function afterCreate(): void
    {
        $record = $this->record;

        // ambil nomor RAB dari field yang ada di modelmu
        $noRab = $record->no_rab
            ?? $record->nomor
            ?? $record->no_pengajuan
            ?? null;

        if ($record->request_teknisi_id) {
            \App\Models\RequestTeknisi::where('id', $record->request_teknisi_id)->update([
                'pengajuan_dinas_id' => $record->id,
                'no_rab'             => $noRab,
            ]);
        }
    }
}
