<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ubah kolom date -> timestamp, data lama tetap kepakai
        DB::statement("
            ALTER TABLE `request_marcomms`
            MODIFY `tanggal_respon`   TIMESTAMP NULL DEFAULT NULL,
            MODIFY `tanggal_terkirim` TIMESTAMP NULL DEFAULT NULL
        ");
    }

    public function down(): void
    {
        // rollback: timestamp -> date lagi
        // (bagian jam akan ter-truncate jadi tanggal saja, tapi tidak error)
        DB::statement("
            ALTER TABLE `request_marcomms`
            MODIFY `tanggal_respon`   DATE NULL DEFAULT NULL,
            MODIFY `tanggal_terkirim` DATE NULL DEFAULT NULL
        ");
    }
};
