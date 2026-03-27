<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Tambahkan kolom JSON sementara
        Schema::table('pengajuans', function (Blueprint $table) {
            $table->json('request_teknisi_id_tmp')->nullable()->after('request_teknisi_id');
        });

        // Pindahkan nilai lama ke JSON ARRAY
        DB::statement("
            UPDATE pengajuans
            SET request_teknisi_id_tmp =
                CASE
                    WHEN request_teknisi_id IS NULL THEN NULL
                    ELSE JSON_ARRAY(request_teknisi_id)
                END
        ");

        // Hapus FK kalau ada (tidak error jika tidak ada)
        Schema::table('pengajuans', function (Blueprint $table) {
            try {
                $table->dropForeign(['request_teknisi_id']);
            } catch (\Throwable $e) {
                // abaikan
            }
        });

        // Hapus kolom lama
        Schema::table('pengajuans', function (Blueprint $table) {
            $table->dropColumn('request_teknisi_id');
        });

        // Rename kolom tmp → request_teknisi_id (JSON)
        DB::statement("
            ALTER TABLE pengajuans
            CHANGE request_teknisi_id_tmp request_teknisi_id JSON NULL
        ");
    }

    public function down(): void
    {
        // Tambah kolom int baru sementara
        Schema::table('pengajuans', function (Blueprint $table) {
            $table->unsignedBigInteger('request_teknisi_id_int_tmp')->nullable()->after('request_teknisi_id');
        });

        // Ambil elemen pertama dari JSON array
        DB::statement("
            UPDATE pengajuans
            SET request_teknisi_id_int_tmp =
                CASE
                    WHEN request_teknisi_id IS NULL THEN NULL
                    ELSE JSON_UNQUOTE(JSON_EXTRACT(request_teknisi_id, '$[0]'))
                END
        ");

        // Hapus kolom JSON
        Schema::table('pengajuans', function (Blueprint $table) {
            $table->dropColumn('request_teknisi_id');
        });

        // Rename kembali jadi INT tunggal
        DB::statement("
            ALTER TABLE pengajuans
            CHANGE request_teknisi_id_int_tmp request_teknisi_id BIGINT UNSIGNED NULL
        ");
    }
};
