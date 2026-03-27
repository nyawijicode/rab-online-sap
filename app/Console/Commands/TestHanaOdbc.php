<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Sap\HanaOdbcConnector;

class TestHanaOdbc extends Command
{
    protected $signature = 'sap:test-hana';
    protected $description = 'Test koneksi ODBC ke SAP HANA dan ambil 1 PO';

    public function handle(HanaOdbcConnector $connector): int
    {
        $this->info('Mencoba koneksi ke HANA via ODBC...');

        $rows = $connector->select('SELECT TOP 1 "DocEntry","DocNum" FROM "SAP"."OPOR" ORDER BY "DocEntry" DESC');

        $this->info('Berhasil ambil data:');
        $this->line(print_r($rows, true));

        return self::SUCCESS;
    }
}
