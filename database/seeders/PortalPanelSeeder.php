<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PortalPanel;

class PortalPanelSeeder extends Seeder
{
    public function run(): void
    {
        $panels = [
            [
                'name'        => 'Panel Admin',
                'code'        => 'admin',
                'url'         => '/admin',
                'badge'       => 'Superadmin',
                'description' => 'Manajemen user, role & permission, konfigurasi modul dan integrasi.',
                'is_active'   => true,
                'sort_order'  => 1,
            ],
            [
                'name'        => 'Panel RAB',
                'code'        => 'rab',
                'url'         => '/rab',
                'badge'       => 'Keuangan / Marcomm',
                'description' => 'Pengajuan Anggaran Biaya, approval berjenjang, monitoring dan laporan.',
                'is_active'   => true,
                'sort_order'  => 2,
            ],
            [
                'name'        => 'Panel Form',
                'code'        => 'form',
                'url'         => '/form',
                'badge'       => 'Form / Request',
                'description' => 'Berbagai form pengajuan internal: IT, HR, operasional, fasilitas, dll.',
                'is_active'   => true,
                'sort_order'  => 3,
            ],
            [
                'name'        => 'Panel QC',
                'code'        => 'qc',
                'url'         => '/qc',
                'badge'       => 'QC / Teknisi',
                'description' => 'Quality check barang, assignment teknisi, dan integrasi hasil QC ke SAP.',
                'is_active'   => true,
                'sort_order'  => 4,
            ],
        ];

        foreach ($panels as $data) {
            PortalPanel::updateOrCreate(
                ['code' => $data['code']],
                $data,
            );
        }
    }
}
