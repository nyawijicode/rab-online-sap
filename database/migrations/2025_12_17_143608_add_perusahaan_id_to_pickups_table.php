<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pickups', function (Blueprint $table) {
            if (! Schema::hasColumn('pickups', 'perusahaan_id')) {
                $table->unsignedBigInteger('perusahaan_id')->nullable()->after('id')->index();
            }
        });

        // Backfill data lama -> set default ke company id=1 (sesuaikan kalau mau)
        DB::table('pickups')->whereNull('perusahaan_id')->update(['perusahaan_id' => 1]);

        // FK ke companies.id
        Schema::table('pickups', function (Blueprint $table) {
            try {
                $table->foreign('perusahaan_id')
                    ->references('id')
                    ->on('companies')
                    ->nullOnDelete();
            } catch (\Throwable $e) {
                // ignore jika sudah ada
            }
        });
    }

    public function down(): void
    {
        Schema::table('pickups', function (Blueprint $table) {
            try {
                $table->dropForeign(['perusahaan_id']);
            } catch (\Throwable $e) {
            }
            if (Schema::hasColumn('pickups', 'perusahaan_id')) {
                $table->dropColumn('perusahaan_id');
            }
        });
    }
};
