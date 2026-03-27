<?php

namespace App\Models\Sap;

use App\Services\Sap\SapHanaService;
use Illuminate\Database\Eloquent\Model;
use Sushi\Sushi;

class SapApInvoice extends Model
{
    use Sushi;

    protected $table = 'sap_ap_invoices';

    // Batasi insert per query untuk menghindari error SQLite "too many SQL variables"
    protected int $sushiInsertChunkSize = 50;

    // Disable timestamps karena data aslinya dari SAP
    public $timestamps = false;

    // Schema minimal
    protected array $schema = [
        'DocEntry'    => 'integer',
        'DocNum'      => 'string',
        'CardCode'    => 'string',
        'CardName'    => 'string',
        'DocDate'     => 'date',
        'DocDueDate'  => 'date',
        'DocTotal'    => 'float',
        'DocStatus'   => 'string',
        'Comments'    => 'string',
        'FakturPajak' => 'string',
    ];

    /**
     * Ambil data dari SAP HANA untuk di-cache oleh Sushi di SQLite in-memory.
     */
    public function getRows(): array
    {
        /** @var SapHanaService $service */
        $service = app(SapHanaService::class);

        return $service->getApInvoices();
    }
}
