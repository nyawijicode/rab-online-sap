<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$divisis = \App\Models\Divisi::all();
foreach ($divisis as $d) {
    echo "ID: {$d->id}, Nama: {$d->nama}\n";
}
