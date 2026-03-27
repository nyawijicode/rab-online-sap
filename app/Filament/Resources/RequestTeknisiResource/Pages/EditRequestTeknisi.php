<?php

namespace App\Filament\Resources\RequestTeknisiResource\Pages;

use App\Filament\Resources\RequestTeknisiResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class EditRequestTeknisi extends EditRecord
{
    protected static string $resource = RequestTeknisiResource::class;
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user   = auth()->user();
        $record = $this->record;

        $canEditReports = $user->hasAnyRole(['superadmin', 'koordinator teknisi'])
            || ($user->id === (int) $record->teknisi_id);

        // Jika status sudah 'selesai' dan bukan superadmin -> kunci status
        if ($record->status === 'selesai' && ! $user->hasRole('superadmin')) {
            $data['status'] = $record->status;
            return $data;
        }

        // Selain superadmin & koordinator teknisi → tidak boleh ubah status
        if (! $user->hasAnyRole(['superadmin', 'koordinator teknisi'])) {
            $data['status'] = $record->status;
        }

        // Jika status existing selesai & bukan superadmin: jangan ubah status
        if ($record->status === 'selesai' && ! $user->hasRole('superadmin')) {
            unset($data['status']);
        }

        // Kalau minimal ada 1 report, otomatis status selesai
        if ($record->reports()->exists() && $record->status !== 'selesai') {
            $record->updateQuietly(['status' => 'selesai']);

            // 🔥 FIX: ganti notify() dengan Notification::make()
            Notification::make()
                ->title('Report teknisi tersimpan')
                ->body('Status berhasil diubah ke Selesai.')
                ->success()
                ->send();
        }

        // Jika user tidak boleh edit reports
        if (! $canEditReports) {
            unset($data['reports']);
        } else {
            // pastikan setiap report memiliki user_id
            if (isset($data['reports']) && is_array($data['reports'])) {
                $data['reports'] = array_map(function ($item) {
                    $item['user_id'] = $item['user_id'] ?? Auth::id();
                    return $item;
                }, $data['reports']);
            }
        }

        if (empty($data['closing'])) {
            $data['id_paket'] = 'Belum Closing';
        }

        $data['nama_dinas'] = $data['nama_dinas'] ?? 'Belum Closing';

        return $data;
    }
}
