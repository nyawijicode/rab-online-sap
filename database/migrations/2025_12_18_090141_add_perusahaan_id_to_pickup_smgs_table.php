<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pickup_smgs', function (Blueprint $table) {
            if (! Schema::hasColumn('pickup_smgs', 'perusahaan_id')) {
                $table->unsignedBigInteger('perusahaan_id')->nullable()->after('id')->index();
            }
        });

        // isi data lama biar tidak null (pakai company id=1, sesuaikan kalau mau)
        DB::table('pickup_smgs')->whereNull('perusahaan_id')->update(['perusahaan_id' => 1]);

        Schema::table('pickup_smgs', function (Blueprint $table) {
            try {
                $table->foreign('perusahaan_id')
                    ->references('id')
                    ->on('companies')
                    ->nullOnDelete();
            } catch (\Throwable $e) {
                // ignore
            }
        });
    }

    public function down(): void
    {
        Schema::table('pickup_smgs', function (Blueprint $table) {
            try {
                $table->dropForeign(['perusahaan_id']);
            } catch (\Throwable $e) {
            }
            if (Schema::hasColumn('pickup_smgs', 'perusahaan_id')) {
                $table->dropColumn('perusahaan_id');
            }
        });
    }
};
