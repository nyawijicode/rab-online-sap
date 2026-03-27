<?php

namespace App\Filament\Pickup\Resources\PickupResource\Pages;

use App\Filament\Pickup\Resources\PickupResource;
use App\Models\Company;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditPickup extends EditRecord
{
    protected static string $resource = PickupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('confirm_delivery')
                ->label('Konfirmasi Pengiriman')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Pengiriman')
                ->modalDescription('Apakah Anda yakin ingin mengkonfirmasi pengiriman ini? Status akan diubah menjadi Completed.')
                ->modalSubmitActionLabel('Ya, Konfirmasi')
                ->visible(function () {
                    // Tampilkan tombol jika:
                    // 1. Ada nomor resi
                    // 2. Status belum completed atau canceled
                    return !empty($this->record->no_resi)
                        && !in_array($this->record->status, ['completed', 'canceled']);
                })
                ->action(function () {
                    $this->record->update(['status' => 'completed']);

                    \Filament\Notifications\Notification::make()
                        ->title('Pengiriman Dikonfirmasi')
                        ->success()
                        ->body('Status pickup telah diubah menjadi Completed.')
                        ->send();
                }),

            Actions\DeleteAction::make(),
        ];
    }
    protected function authorizeAccess(): void
    {
        parent::authorizeAccess();

        $user = auth()->user();

        // superadmin bebas
        if ($user?->hasRole('superadmin')) {
            return;
        }

        // selain superadmin: kalau completed -> forbidden
        if ($this->record->status === 'completed') {
            abort(403, 'Pickup sudah Completed, tidak bisa diedit.');
        }
    }
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = Auth::id();

        return $data;
    }
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
