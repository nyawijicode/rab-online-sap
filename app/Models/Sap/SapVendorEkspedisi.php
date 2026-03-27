<?php

namespace App\Models\Sap;

use App\Services\Sap\SapHanaService;
use Illuminate\Database\Eloquent\Model;
use Sushi\Sushi;

class SapVendorEkspedisi extends Model
{
    use Sushi;

    protected $table = 'sap_vendor_ekspedisi';

    protected $primaryKey = 'CardCode';

    public $incrementing = false;
    public $timestamps = false;

    /**
     * Schema penting supaya Filament aman meskipun dataset kosong.
     */
    protected $schema = [
        'CardCode'    => 'string',
        'CardName'    => 'string',
        'CardType'    => 'string',
        'GroupCode'   => 'integer',
        'GroupName'   => 'string',
        'LicTradNum'  => 'string',
        'Phone1'      => 'string',
        'Phone2'      => 'string',
        'Cellular'    => 'string',
        'Fax'         => 'string',
        'E_Mail'      => 'string',
        'IntrntSite'  => 'string',
        'CntctPrsn'   => 'string',
        'Balance'     => 'float',
        'Currency'    => 'string',
        'Address'     => 'string',
        'ZipCode'     => 'string',
        'City'        => 'string',
        'County'      => 'string',
        'Country'     => 'string',
        'Notes'       => 'string',
        'CreateDate'  => 'string',
        'UpdateDate'  => 'string',
    ];

    /**
     * Batasi jumlah baris per INSERT agar tidak melebihi batas SQLite variable (999).
     * 52 baris × 23 kolom = 1196 variables → melebihi batas di SQLite versi lama (cPanel).
     * Chunk 20 baris × 23 kolom = 460 variables → aman.
     */
    protected int $sushiInsertChunkSize = 20;

    protected function getRows(): array
    {
        $service = app(SapHanaService::class);

        $rows = $service->getVendorsEkspedisi();

        // pastikan key konsisten dan null-safe
        return collect($rows)->map(function (array $r) {
            return [
                'CardCode'   => (string) ($r['CardCode'] ?? ''),
                'CardName'   => (string) ($r['CardName'] ?? ''),
                'CardType'   => (string) ($r['CardType'] ?? ''),
                'GroupCode'  => isset($r['GroupCode']) ? (int) $r['GroupCode'] : null,
                'GroupName'  => (string) ($r['GroupName'] ?? ''),
                'LicTradNum' => (string) ($r['LicTradNum'] ?? ''),
                'Phone1'     => (string) ($r['Phone1'] ?? ''),
                'Phone2'     => (string) ($r['Phone2'] ?? ''),
                'Cellular'   => (string) ($r['Cellular'] ?? ''),
                'Fax'        => (string) ($r['Fax'] ?? ''),
                'E_Mail'     => (string) ($r['E_Mail'] ?? ''),
                'IntrntSite' => (string) ($r['IntrntSite'] ?? ''),
                'CntctPrsn'  => (string) ($r['CntctPrsn'] ?? ''),
                'Balance'    => isset($r['Balance']) ? (float) $r['Balance'] : null,
                'Currency'   => (string) ($r['Currency'] ?? ''),
                'Address'    => (string) ($r['Address'] ?? ''),
                'ZipCode'    => (string) ($r['ZipCode'] ?? ''),
                'City'       => (string) ($r['City'] ?? ''),
                'County'     => (string) ($r['County'] ?? ''),
                'Country'    => (string) ($r['Country'] ?? ''),
                'Notes'      => (string) ($r['Notes'] ?? ''),
                'CreateDate' => (string) ($r['CreateDate'] ?? ''),
                'UpdateDate' => (string) ($r['UpdateDate'] ?? ''),
            ];
        })->values()->all();
    }
}
