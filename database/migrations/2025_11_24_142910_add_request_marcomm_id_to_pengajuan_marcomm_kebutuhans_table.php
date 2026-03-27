<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengajuan_marcomm_kebutuhans', function (Blueprint $table) {
            // nullable supaya data lama aman
            $table->unsignedBigInteger('request_marcomm_id')->nullable()->after('pengajuan_id');

            $table->foreign('request_marcomm_id')
                ->references('id')
                ->on('request_marcomms')
                ->nullOnDelete()   // kalau request_marcomm dihapus, kolom ini jadi NULL
                ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::table('pengajuan_marcomm_kebutuhans', function (Blueprint $table) {
            $table->dropForeign(['request_marcomm_id']);
            $table->dropColumn('request_marcomm_id');
        });
    }
};
