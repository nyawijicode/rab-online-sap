<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix lampiran_marcomm_kegiatans_pusat
        Schema::table('lampiran_marcomm_kegiatans_pusat', function (Blueprint $table) {
            // 1. Drop fk lama
            $table->dropForeign(['pengajuan_id']);

            // 2. Add fk baru ke tabel pengajuans
            $table->foreign('pengajuan_id')
                ->references('id')
                ->on('pengajuans')
                ->onDelete('cascade');
        });

        // Fix lampiran_marcomm_kegiatans_cabang
        Schema::table('lampiran_marcomm_kegiatans_cabang', function (Blueprint $table) {
            // 1. Drop fk lama
            $table->dropForeign(['pengajuan_id']);

            // 2. Add fk baru ke tabel pengajuans
            $table->foreign('pengajuan_id')
                ->references('id')
                ->on('pengajuans')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert lampiran_marcomm_kegiatans_pusat
        Schema::table('lampiran_marcomm_kegiatans_pusat', function (Blueprint $table) {
            $table->dropForeign(['pengajuan_id']);
            $table->foreign('pengajuan_id')
                ->references('id')
                ->on('pengajuan_marcomm_kegiatans')
                ->onDelete('cascade');
        });

        // Revert lampiran_marcomm_kegiatans_cabang
        Schema::table('lampiran_marcomm_kegiatans_cabang', function (Blueprint $table) {
            $table->dropForeign(['pengajuan_id']);
            $table->foreign('pengajuan_id')
                ->references('id')
                ->on('pengajuan_marcomm_kegiatans')
                ->onDelete('cascade');
        });
    }
};
