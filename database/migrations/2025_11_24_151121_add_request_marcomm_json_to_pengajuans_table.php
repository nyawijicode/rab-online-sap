<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            // toggle untuk nyalain fitur request marcomm
            $table->boolean('menggunakan_request_marcomm')
                ->default(false)
                ->after('menggunakan_teknisi');

            // JSON array id request_marcomm (sama konsepnya dengan request_teknisi_id)
            $table->json('request_marcomm_id')
                ->nullable()
                ->after('menggunakan_request_marcomm');

            // opsional: ringkasan text (biar bisa tampil di UI)
            $table->string('request_marcomm_ringkasan')
                ->nullable()
                ->after('request_marcomm_id');
        });
    }

    public function down(): void
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            $table->dropColumn([
                'menggunakan_request_marcomm',
                'request_marcomm_id',
                'request_marcomm_ringkasan',
            ]);
        });
    }
};
