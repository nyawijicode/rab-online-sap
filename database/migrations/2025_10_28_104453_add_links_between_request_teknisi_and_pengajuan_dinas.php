<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1) tambah kolom pada request_teknisis (jika belum)
        Schema::table('request_teknisis', function (Blueprint $table) {
            if (! Schema::hasColumn('request_teknisis','pengajuan_dinas_id')) {
                $table->unsignedBigInteger('pengajuan_dinas_id')->nullable()->after('user_id');
                $table->foreign('pengajuan_dinas_id')->references('id')->on('pengajuan_dinas')->nullOnDelete();
            }
            if (! Schema::hasColumn('request_teknisis','no_rab')) {
                $table->string('no_rab')->nullable()->after('pengajuan_dinas_id');
            }
        });

        // 2) tambah kolom di pengajuan_dinas untuk menyimpan pilihan request teknisi (toggle tetap pakai 'menggunakan_teknisi')
        Schema::table('pengajuan_dinas', function (Blueprint $table) {
            if (! Schema::hasColumn('pengajuan_dinas','request_teknisi_id')) {
                $table->unsignedBigInteger('request_teknisi_id')->nullable()->after('menggunakan_teknisi');
                $table->foreign('request_teknisi_id')->references('id')->on('request_teknisis')->nullOnDelete();
            }
            if (! Schema::hasColumn('pengajuan_dinas','request_teknisi_nama_dinas')) {
                $table->string('request_teknisi_nama_dinas')->nullable()->after('request_teknisi_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('request_teknisis', function (Blueprint $table) {
            if (Schema::hasColumn('request_teknisis','pengajuan_dinas_id')) {
                $table->dropForeign(['pengajuan_dinas_id']);
                $table->dropColumn('pengajuan_dinas_id');
            }
            if (Schema::hasColumn('request_teknisis','no_rab')) {
                $table->dropColumn('no_rab');
            }
        });

        Schema::table('pengajuan_dinas', function (Blueprint $table) {
            if (Schema::hasColumn('pengajuan_dinas','request_teknisi_id')) {
                $table->dropForeign(['request_teknisi_id']);
                $table->dropColumn('request_teknisi_id');
            }
            if (Schema::hasColumn('pengajuan_dinas','request_teknisi_nama_dinas')) {
                $table->dropColumn('request_teknisi_nama_dinas');
            }
        });
    }
};
