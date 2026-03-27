<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('request_marcomms', function (Blueprint $table) {
            // nullable: 1 request_marcomm boleh belum punya pengajuan
            $table->unsignedBigInteger('pengajuan_id')->nullable()->after('id');

            $table->foreign('pengajuan_id')
                ->references('id')
                ->on('pengajuans')
                ->nullOnDelete()   // kalau pengajuan dihapus, relasi di sini di-null-kan
                ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::table('request_marcomms', function (Blueprint $table) {
            $table->dropForeign(['pengajuan_id']);
            $table->dropColumn('pengajuan_id');
        });
    }
};
