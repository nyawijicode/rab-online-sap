<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('request_teknisis', function (Blueprint $table) {
            $table->string('company_code', 20)
                ->after('id')
                ->default('sap')
                ->index();
        });

        // Pastikan data lama aman → arahkan ke SAP (kode='sap')
        DB::table('request_teknisis')
            ->whereNull('company_code')
            ->update(['company_code' => 'sap']);

        Schema::table('request_teknisis', function (Blueprint $table) {
            $table->foreign('company_code')
                ->references('kode')
                ->on('companies')
                ->cascadeOnUpdate() // Jika kode di companies berubah, ikut berubah
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('request_teknisis', function (Blueprint $table) {
            $table->dropForeign(['company_code']);
            $table->dropColumn('company_code');
        });
    }
};
