<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $exists = Illuminate\Support\Facades\Schema::hasColumn('persetujuan_approvers', 'role');
    echo $exists ? "COLUMN EXISTS" : "COLUMN MISSING";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
