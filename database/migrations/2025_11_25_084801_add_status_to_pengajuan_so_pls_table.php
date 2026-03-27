<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengajuan_so_pls', function (Blueprint $table) {
            $table->enum('status', ['pending', 'proses', 'selesai', 'ditolak'])
                ->default('pending')
                ->after('tanggal_respon');
        });
    }

    public function down(): void
    {
        Schema::table('pengajuan_so_pls', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
