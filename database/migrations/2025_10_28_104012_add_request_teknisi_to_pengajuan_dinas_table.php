<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pengajuan_dinas', function (Blueprint $table) {
            $table->boolean('use_request_teknisi')->default(false);
            $table->foreignId('request_teknisi_id')->nullable()->constrained('request_teknisis')->nullOnDelete();
            $table->string('request_teknisi_nama_dinas')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pengajuan_dinas', function (Blueprint $table) {
            if (Schema::hasColumn('pengajuan_dinas','request_teknisi_id')) {
                $table->dropConstrainedForeignId('request_teknisi_id');
            }
            $table->dropColumn(['use_request_teknisi','request_teknisi_nama_dinas']);
        });
    }
};
