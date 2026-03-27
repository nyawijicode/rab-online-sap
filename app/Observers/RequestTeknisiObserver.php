<?php

namespace App\Observers;

use App\Models\RequestTeknisi;
use App\Models\RequestTeknisiHistory;
use Illuminate\Support\Facades\Auth;

class RequestTeknisiObserver
{
    public function created(RequestTeknisi $requestTeknisi): void
    {
        RequestTeknisiHistory::create([
            'request_teknisi_id' => $requestTeknisi->id,
            'user_id'            => Auth::id() ?? $requestTeknisi->user_id, // Fallback to owner if no auth
            'description'        => 'Permintaan dibuat',
        ]);
    }

    public function updated(RequestTeknisi $requestTeknisi): void
    {
        $user = Auth::user();
        $userId = $user ? $user->id : null;

        // Check for specific fields we want to track
        if ($requestTeknisi->isDirty('status')) {
            RequestTeknisiHistory::create([
                'request_teknisi_id' => $requestTeknisi->id,
                'user_id'            => $userId,
                'field'              => 'status',
                'old_value'          => $requestTeknisi->getOriginal('status'),
                'new_value'          => $requestTeknisi->status,
                'description'        => 'Status berubah dari ' . $requestTeknisi->getOriginal('status') . ' menjadi ' . $requestTeknisi->status,
            ]);
        }

        if ($requestTeknisi->isDirty('tanggal_penjadwalan')) {
            $old = $requestTeknisi->getOriginal('tanggal_penjadwalan');
            $new = $requestTeknisi->tanggal_penjadwalan;
            // Format date for display if not null
            $oldStr = $old ? \Carbon\Carbon::parse($old)->translatedFormat('d M Y') : '-';
            $newStr = $new ? \Carbon\Carbon::parse($new)->translatedFormat('d M Y') : '-';

            RequestTeknisiHistory::create([
                'request_teknisi_id' => $requestTeknisi->id,
                'user_id'            => $userId,
                'field'              => 'tanggal_penjadwalan',
                'old_value'          => $oldStr,
                'new_value'          => $newStr,
                'description'        => 'Jadwal berubah dari ' . $oldStr . ' menjadi ' . $newStr,
            ]);
        }

        if ($requestTeknisi->isDirty('final_status')) {
            RequestTeknisiHistory::create([
                'request_teknisi_id' => $requestTeknisi->id,
                'user_id'            => $userId,
                'field'              => 'final_status',
                'old_value'          => $requestTeknisi->getOriginal('final_status'),
                'new_value'          => $requestTeknisi->final_status,
                'description'        => 'Keputusan Akhir: ' . $requestTeknisi->final_status,
            ]);
        }
    }
}
