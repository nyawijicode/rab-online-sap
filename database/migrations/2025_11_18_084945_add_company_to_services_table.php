<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            // Tambah kolom company, nullable dulu biar tidak error
            $table->string('company', 50)->nullable()->after('jenis_servis');
        });

        // Isi semua data lama dengan "sap"
        DB::table('services')->update([
            'company' => 'sap',
        ]);

        // Jadikan NOT NULL setelah semua terisi
        Schema::table('services', function (Blueprint $table) {
            $table->string('company', 50)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('company');
        });
    }
};
