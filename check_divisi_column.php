<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $hasDivisi = Illuminate\Support\Facades\Schema::hasColumn('persetujuan_approvers', 'divisi_id');
    $hasRole = Illuminate\Support\Facades\Schema::hasColumn('persetujuan_approvers', 'role');

    echo "DIVISI_ID: " . ($hasDivisi ? "EXISTS" : "MISSING") . "\n";
    echo "ROLE: " . ($hasRole ? "EXISTS" : "REMOVED") . "\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
