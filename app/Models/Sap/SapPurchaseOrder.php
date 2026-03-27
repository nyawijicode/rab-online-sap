<?php

namespace App\Models\Sap;

use App\Services\Sap\SapHanaService;
use Illuminate\Database\Eloquent\Model;
use Sushi\Sushi;

class SapPurchaseOrder extends Model
{
    use Sushi;

    protected $table = 'sap_purchase_orders';

    protected $primaryKey = 'id';

    public $incrementing = false;
    public $timestamps = false;

    /**
     * Biar nggak kena error "too many SQL variables" di SQLite.
     * Default Sushi = 100, kita kecilkan jadi 40 supaya aman.
     * 40 row x 10 kolom = 400 variable < batas 999.
     */
    public $sushiInsertChunkSize = 40;

    // Schema supaya aman kalau dataset kosong
    protected $schema = [
        'id'         => 'integer',
        'DocEntry'   => 'integer',
        'DocNum'     => 'string',
        'CardCode'   => 'string',
        'CardName'   => 'string',
        'DocDate'    => 'string',
        'DocDueDate' => 'string',
        'DocTotal'   => 'float',
        'DocStatus'  => 'string',
        'PackageId'  => 'string',
    ];

    protected function getRows(): array
    {
        /** @var SapHanaService $service */
        $service = app(SapHanaService::class);

        // Hanya ambil PO dengan Status Pickup2 = 'Y' (UDF U_SOL_STATUS_PICKUP2)
        $rows = $service->getPurchaseOrders(statusPickup2: 'Y');

        return collect($rows)
            ->map(function (array $row) {
                // jadikan DocEntry sebagai primary key lokal
                $row['id'] = $row['DocEntry'];

                // pastikan selalu ada kolom PackageId (string, bisa kosong)
                $row['PackageId'] = isset($row['PackageId'])
                    ? (string) $row['PackageId']
                    : '';

                return $row;
            })
            ->values()
            ->all();
    }
}
