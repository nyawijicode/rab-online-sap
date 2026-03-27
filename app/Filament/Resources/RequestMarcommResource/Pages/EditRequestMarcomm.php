<?php

namespace App\Filament\Resources\RequestMarcommResource\Pages;

use App\Filament\Resources\RequestMarcommResource;
use App\Models\RequestMarcomm;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;

class EditRequestMarcomm extends EditRecord
{
    protected static string $resource = RequestMarcommResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () =>
                    Auth::user()?->hasRole('superadmin')
                    || Auth::id() === $this->record->user_id
                ),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        // superadmin selalu boleh
        if ($user->hasRole('superadmin')) {
            return true;
        }

        // ambil parameter "record" dari Filament (bisa id / array / model)
        $param = $parameters['record'] ?? null;
        if (! $param) {
            return false;
        }

        // normalisasi jadi satu instance RequestMarcomm
        if ($param instanceof RequestMarcomm) {
            $record = $param;
        } elseif (is_array($param)) {
            // misal: ['id' => 5] atau ['record' => 5]
            $id = $param['id'] ?? $param['record'] ?? null;
            if (! $id) {
                return false;
            }
            $record = RequestMarcomm::query()->find($id);
        } else {
            // string/int id
            $record = RequestMarcomm::query()->find($param);
        }

        // kalau entah bagaimana masih Collection, ambil first
        if ($record instanceof Collection) {
            $record = $record->first();
        }

        if (! $record instanceof RequestMarcomm) {
            return false;
        }

        // hanya pemilik request yang boleh
        return (int) $record->user_id === (int) $user->id;
    }
}
