<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('request_teknisis', function (Blueprint $table) {
            // boolean NOT NULL dengan default true agar data lama otomatis bernilai true
            $table->boolean('closing')->default(true)->after('id_paket');
        });

        // Jaga-jaga: pastikan semua baris lama terisi true
        DB::table('request_teknisis')
            ->whereNull('closing')
            ->update(['closing' => true]);
    }

    public function down(): void
    {
        Schema::table('request_teknisis', function (Blueprint $table) {
            $table->dropColumn('closing');
        });
    }
};
