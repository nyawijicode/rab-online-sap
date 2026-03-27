<?php

namespace App\Models\Sap;

use App\Services\Sap\SapHanaService;
use Illuminate\Database\Eloquent\Model;
use Sushi\Sushi;

class SapProject extends Model
{
    use Sushi;

    protected $table = 'sap_projects';

    protected $primaryKey = 'id';

    public $incrementing = false;
    public $timestamps = false;

    /**
     * Schema minimal supaya Sushi nggak error kalau dataset kosong.
     */
    protected $schema = [
        'id'         => 'integer',
        'PrjCode'    => 'string',
        'PrjName'    => 'string',
        'ValidFrom'  => 'string',
        'ValidTo'    => 'string',
        'Active'     => 'string',
    ];

    /**
     * Ambil data dari SAP (OPRJ) lewat SapHanaService.
     */
    protected function getRows(): array
    {
        /** @var SapHanaService $service */
        $service = app(SapHanaService::class);

        $rows = $service->getProjects();

        // Tambah kolom id sebagai primary key lokal
        $i = 1;

        return collect($rows)
            ->map(function (array $row) use (&$i) {
                // pastikan key persis sama schema
                $row = [
                    'id'        => $i++,
                    'PrjCode'   => (string) ($row['PrjCode'] ?? ''),
                    'PrjName'   => (string) ($row['PrjName'] ?? ''),
                    'ValidFrom' => (string) ($row['ValidFrom'] ?? ''),
                    'ValidTo'   => (string) ($row['ValidTo'] ?? ''),
                    'Active'    => (string) ($row['Active'] ?? ''),
                ];

                return $row;
            })
            ->values()
            ->all();
    }
}
