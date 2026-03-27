<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // pastikan kolom ada
        if (Schema::hasColumn('pickups', 'po_docentry')) {
            // ubah jadi nullable
            DB::statement('ALTER TABLE `pickups` MODIFY `po_docentry` BIGINT UNSIGNED NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('pickups', 'po_docentry')) {
            // balikin jadi NOT NULL (kalau sebelumnya memang begitu)
            DB::statement('ALTER TABLE `pickups` MODIFY `po_docentry` BIGINT UNSIGNED NOT NULL');
        }
    }
};
