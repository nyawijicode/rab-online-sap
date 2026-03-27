<?php

namespace App\Models\Sap;

use App\Services\Sap\SapHanaService;
use Illuminate\Database\Eloquent\Model;
use Sushi\Sushi;

class SapVendor extends Model
{
    use Sushi;

    protected $table = 'sap_vendors';

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    // Schema minimal supaya Sushi tidak bikin "create table sap_vendors ()"
    protected $schema = [
        'id'        => 'string',
        'CardCode'  => 'string',
        'CardName'  => 'string',
        'CardType'  => 'string',
        'GroupCode' => 'integer',
        'Phone1'    => 'string',
        'Cellular'  => 'string',
        'Email'     => 'string',
        'Currency'  => 'string',
    ];

    protected function getRows(): array
    {
        /** @var SapHanaService $service */
        $service = app(SapHanaService::class);

        $rows = $service->getVendors(); // semua OCRD

        return collect($rows)
            ->map(function (array $row) {
                $row['id'] = $row['CardCode'];   // pakai CardCode sebagai PK
                return $row;
            })
            ->values()
            ->all();
    }
}
