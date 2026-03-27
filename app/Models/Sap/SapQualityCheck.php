<?php

namespace App\Models\Sap;

use App\Services\Sap\SapHanaService;
use Illuminate\Database\Eloquent\Model;
use Sushi\Sushi;

class SapQualityCheck extends Model
{
    use Sushi;

    protected $table = 'sap_quality_checks';

    protected $primaryKey = 'DocEntry';
    public $incrementing = true;
    public $timestamps = false;

    protected function getRows(): array
    {
        /** @var SapHanaService $service */
        $service = app(SapHanaService::class);

        // sudah ter-alias: DocEntry, QCNo, GrpoNo, Branch, QCDate, Status
        return $service->getQualityChecks();
    }
}
