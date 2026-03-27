<?php

namespace App\Models\Sap;

use App\Services\Sap\SapHanaService;
use Illuminate\Database\Eloquent\Model;
use Sushi\Sushi;

class SapQualityCheckSerial extends Model
{
    use Sushi;

    protected $table = 'sap_quality_check_serials';
    protected $primaryKey = 'LineId';
    public $incrementing = false;
    public $timestamps = false;

    public static int $docEntryFilter = 0;

    protected function getRows(): array
    {
        if (! static::$docEntryFilter) return [];

        $service = app(SapHanaService::class);
        $serial = $service->getQualityCheckDetail(static::$docEntryFilter);

        return $serial['serials'];
    }
}
