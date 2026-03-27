<?php

namespace App\Filament\Resources\PengajuanDinasResource\Pages;

use App\Filament\Resources\PengajuanDinasResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPengajuanDinas extends EditRecord
{
    protected static string $resource = PengajuanDinasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    protected function afterSave(): void
    {
        $record = $this->record;

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
