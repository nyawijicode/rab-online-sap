<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$tables = ['lampiran_marcomm_kegiatans_pusat', 'lampiran_marcomm_kegiatans_cabang'];

foreach ($tables as $table) {
    echo "Checking table: $table\n";
    $keys = DB::select("
        SELECT 
            CONSTRAINT_NAME, 
            COLUMN_NAME, 
            REFERENCED_TABLE_NAME, 
            REFERENCED_COLUMN_NAME
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE 
            TABLE_SCHEMA = '" . env('DB_DATABASE') . "' AND
            TABLE_NAME = '$table' AND
            REFERENCED_TABLE_NAME IS NOT NULL
    ");

    foreach ($keys as $key) {
        echo "  - Constraint: {$key->CONSTRAINT_NAME}\n";
        echo "    Column: {$key->COLUMN_NAME}\n";
        echo "    References: {$key->REFERENCED_TABLE_NAME}.{$key->REFERENCED_COLUMN_NAME}\n";
    }
    echo "\n";
}
