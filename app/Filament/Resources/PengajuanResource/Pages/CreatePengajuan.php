<?php

namespace App\Filament\Resources\PengajuanResource\Pages;

use App\Filament\Resources\PengajuanResource;
use App\Models\Lampiran;
use App\Models\PengajuanMarcommKebutuhan;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\PengajuanStatus;
use App\Models\Persetujuan;
use App\Models\PersetujuanApprover;
use App\Models\RequestMarcomm;
use Illuminate\Support\Facades\Log;
use App\Models\RequestTeknisi;

class CreatePengajuan extends CreateRecord
{
    protected static string $resource = PengajuanResource::class;

    protected function afterCreate(): void
    {
        $record   = $this->record;
        $formData = $this->data;

        // ===========================
        // LINK REQUEST TEKNISI -> PENGAJUAN
        // ===========================
        if (! empty($record->request_teknisi_id)) {
            RequestTeknisi::linkToPengajuan($record->request_teknisi_id, $record);
        }
        if (! empty($record->request_marcomm_id)) {
            \App\Models\RequestMarcomm::linkToPengajuan($record->request_marcomm_id, $record);
        }
        $pengajuan = $record;

        Lampiran::updateOrCreate(
            ['pengajuan_id' => $pengajuan->id],
            [
                'lampiran_asset'             => $formData['lampiran_asset'] ?? false,
                'lampiran_biaya_service'     => $formData['lampiran_biaya_service'] ?? false,
                'lampiran_dinas'             => $formData['lampiran_dinas'] ?? false,
                'lampiran_marcomm_promosi'   => $formData['lampiran_marcomm_promosi'] ?? false,
                'lampiran_marcomm_kebutuhan' => $formData['lampiran_marcomm_kebutuhan'] ?? false,
            ]
        );

        // ====== PAKSA SIMPAN TOGGLE & TOTAL AMPLOP ======
        $amplopOn = ! empty($formData['kebutuhan_amplop']);
        \App\Models\PengajuanMarcommKebutuhan::writeAmplopToggle($pengajuan->id, $amplopOn);
        \App\Models\PengajuanMarcommKebutuhan::syncTotalAmplop($pengajuan->id);

        $kartuOn = ! empty($formData['kebutuhan_kartu']);
        \App\Models\PengajuanMarcommKebutuhan::writeKartuToggle($pengajuan->id, $kartuOn);

        $kemejaOn = ! empty($formData['kebutuhan_kemeja']);
        \App\Models\PengajuanMarcommKebutuhan::writeKemejaToggle($pengajuan->id, $kemejaOn);

        $pusatOn = ! empty($formData['tim_pusat']);
        \App\Models\PengajuanMarcommKegiatan::writePusatToggle($pengajuan->id, $pusatOn);

        $cabangOn = ! empty($formData['tim_cabang']);
        \App\Models\PengajuanMarcommKegiatan::writeCabangToggle($pengajuan->id, $cabangOn);

        Log::info('Running afterCreate for pengajuan ID: ' . $pengajuan->id);

        $persetujuans = \App\Models\Persetujuan::with(['pengajuanApprovers.approver.roles'])
            ->where('user_id', $pengajuan->user_id)
            ->where('company', $pengajuan->company) // Filter berdasarkan company pengajuan
            ->get();

        Log::info('Jumlah persetujuan ditemukan: ' . $persetujuans->count());

        foreach ($persetujuans as $persetujuan) {
            $skipTeknisi      = ! ($pengajuan->menggunakan_teknisi && $persetujuan->menggunakan_teknisi);
            $skipAssetTeknisi = ! ($pengajuan->asset_teknisi && $persetujuan->asset_teknisi);

            // --- Perbaiki logika pengiriman ---
            $adaPengajuanPengiriman   = $pengajuan->use_pengiriman || $pengajuan->use_car;
            $adaPersetujuanPengiriman = $persetujuan->use_pengiriman || $persetujuan->use_car;
            $skipPengiriman           = ! ($adaPengajuanPengiriman && $adaPersetujuanPengiriman);

            foreach ($persetujuan->pengajuanApprovers as $approver) {
                $user = $approver->approver;
                if (! $user) {
                    continue;
                }

                $roleNames = $user->getRoleNames();
                $assignedDivisi = $approver->divisi;
                $divisiName = $assignedDivisi ? strtolower($assignedDivisi->nama) : '';

                $isKoordinatorTeknisi = $roleNames->contains('koordinator teknisi') || str_contains($divisiName, 'teknisi');
                $isKoordinatorGudang  = $roleNames->contains('koordinator gudang') || str_contains($divisiName, 'gudang');
                $isManager            = $roleNames->contains('manager') || str_contains($divisiName, 'manager');
                $isKomisaris          = $roleNames->contains('komisaris') || str_contains($divisiName, 'komisaris');
                $isDirektur           = $roleNames->contains('direktur') || str_contains($divisiName, 'direktur');
                $isOwner              = $roleNames->contains('owner') || str_contains($divisiName, 'owner');
                $isRt                 = $roleNames->contains('rt') || str_contains($divisiName, 'rt') || str_contains($divisiName, 'hrdga') || str_contains($divisiName, 'rumah tangga');

                // ❌ Skip jika kondisi tidak memenuhi
                if ($isKoordinatorTeknisi && $skipTeknisi) {
                    Log::info("❌ Skip Koordinator Teknisi: user_id {$user->id}");
                    continue;
                }

                if ($isRt && $skipAssetTeknisi) {
                    Log::info("❌ Skip RT (Asset Teknisi): user_id {$user->id}");
                    continue;
                }

                if ($isKoordinatorGudang && $skipPengiriman) {
                    Log::info("❌ Skip Koordinator Gudang (Pengiriman): user_id {$user->id}");
                    continue;
                }

                // -------------------
                // LOGIKA MANAGER FIX
                if ($isManager && $persetujuan->use_manager) {
                    if ($pengajuan->total_biaya < 1000000) {
                        Log::info("❌ Skip Manager: user_id {$user->id} (use_manager = true, nominal < 1jt)");
                        continue;
                    }
                }

                // ===============================================
                // LOGIKA AUTO-APPROVE (Global Role Only)
                // ===============================================
                $autoApprove   = false;
                $autoApproveBy = null;

                // Hanya auto-approve jika user MEMANG memiliki role global tersebut,
                // bukan karena assigned division.
                if ($user->hasRole('komisaris') && $persetujuan->use_komisaris) {
                    $autoApprove   = true;
                    $autoApproveBy = 'komisaris';
                }

                if ($user->hasRole('direktur') && $persetujuan->use_direktur) {
                    $autoApprove   = true;
                    $autoApproveBy = 'direktur';
                }

                if ($user->hasRole('owner') && $persetujuan->use_owner) {
                    $autoApprove   = true;
                    $autoApproveBy = 'owner';
                }

                $userStatus = \App\Models\UserStatus::where('user_id', $pengajuan->user_id)->first();
                if ($pengajuan->is_urgent && $userStatus && in_array($userStatus->atasan_id, [1, 16])) {
                    $autoApprove   = true;
                    $autoApproveBy = 'atasan langsung dadakan (' . $userStatus->atasan_id . ')';
                }

                // ===============================================
                // PERBAIKAN: updateOrCreate supaya tidak nabrak
                // UNIQUE (pengajuan_id, user_id)
                // ===============================================
                $status = PengajuanStatus::updateOrCreate(
                    [
                        'pengajuan_id' => $pengajuan->id,
                        'user_id'      => $user->id,
                    ],
                    [
                        'persetujuan_id' => $persetujuan->id,
                        'is_approved'    => $autoApprove ? true : null,
                        'approved_at'    => $autoApprove ? now() : null,
                    ]
                );

                Log::info(
                    "✅ Disimpan: user_id {$user->id}"
                        . ($autoApprove ? " (auto approve {$autoApproveBy})" : '')
                        . " | status_id {$status->id}"
                );
            }
        }

        // ===========================
        // CEK STATUS AKHIR (AUTO SELESAI)
        // ===========================
        // Jika semua approver sudah auto-approve, set status pengajuan jadi 'selesai'
        $totalApprovers = \App\Models\PengajuanStatus::where('pengajuan_id', $pengajuan->id)->count();
        $totalApproved  = \App\Models\PengajuanStatus::where('pengajuan_id', $pengajuan->id)
            ->where('is_approved', true)
            ->count();

        if ($totalApprovers > 0 && $totalApprovers === $totalApproved) {
            $updateData = ['status' => 'selesai'];
            if ($pengajuan->is_urgent) {
                $updateData['urgent_approved'] = true;
                $updateData['urgent_approved_at'] = now();
                // Optionally set urgent_approved_by, maybe to the atasan_id? 
                // We could just leave it or set a dummy value if needed, but normally true is enough.
            }
            $pengajuan->update($updateData);
            Log::info("🎉 Pengajuan ID {$pengajuan->id} otomatis SELESAI (semua auto-approve).");
        }

        // setelah form & RELATIONSHIPS tersimpan
        $total = $this->record->calculateTotalBiaya();
        $this->record->updateQuietly(['total_biaya' => $total]);

        // refresh state supaya field di UI ikut angka baru
        $this->fillForm();
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
