<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // a) kolom pilihan Request Teknisi di pengajuans
        Schema::table('pengajuans', function (Blueprint $table) {
            if (!Schema::hasColumn('pengajuans', 'request_teknisi_id')) {
                $table->unsignedBigInteger('request_teknisi_id')->nullable()->after('menggunakan_teknisi');
                $table->foreign('request_teknisi_id')->references('id')->on('request_teknisis')->nullOnDelete();
            }
            if (!Schema::hasColumn('pengajuans', 'request_teknisi_nama_dinas')) {
                $table->string('request_teknisi_nama_dinas')->nullable()->after('request_teknisi_id');
            }
        });

        // b) back-reference ke pengajuans pada request_teknisis
        Schema::table('request_teknisis', function (Blueprint $table) {
            if (!Schema::hasColumn('request_teknisis','pengajuan_id')) {
                $table->unsignedBigInteger('pengajuan_id')->nullable()->after('user_id');
                $table->foreign('pengajuan_id')->references('id')->on('pengajuans')->nullOnDelete();
            }
            if (!Schema::hasColumn('request_teknisis','no_rab')) {
                $table->string('no_rab')->nullable()->after('pengajuan_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            if (Schema::hasColumn('pengajuans', 'request_teknisi_id')) {
                $table->dropForeign(['request_teknisi_id']);
                $table->dropColumn(['request_teknisi_id']);
            }
            if (Schema::hasColumn('pengajuans','request_teknisi_nama_dinas')) {
                $table->dropColumn('request_teknisi_nama_dinas');
            }
        });

        Schema::table('request_teknisis', function (Blueprint $table) {
            if (Schema::hasColumn('request_teknisis','pengajuan_id')) {
                $table->dropForeign(['pengajuan_id']);
                $table->dropColumn('pengajuan_id');
            }
            if (Schema::hasColumn('request_teknisis','no_rab')) {
                $table->dropColumn('no_rab');
            }
        });
    }
};
