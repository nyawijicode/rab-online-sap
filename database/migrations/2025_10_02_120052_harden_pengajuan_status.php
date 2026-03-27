<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pengajuan_statuses', function (Blueprint $table) {
            // nama tabelmu bisa 'pengajuan_status' -> sesuaikan
            if (Schema::hasColumn('pengajuan_statuses', 'persetujuan_id')) {
                $table->unsignedBigInteger('persetujuan_id')->nullable()->change();
            }

            // buwang FK lawas yen ana, terus gawe sing anyar SET NULL
            try {
                $table->dropForeign(['persetujuan_id']);
            } catch (\Throwable $e) {
            }
            $table->foreign('persetujuan_id')
                ->references('id')->on('persetujuans')
                ->nullOnDelete(); // ON DELETE SET NULL
        });

        // cegah dobel status per (pengajuan_id, user_id)
        Schema::table('pengajuan_statuses', function (Blueprint $table) {
            $table->unique(['pengajuan_id', 'user_id'], 'pengajuan_user_unique');
        });
    }

    public function down(): void
    {
        Schema::table('pengajuan_statuses', function (Blueprint $table) {
            try {
                $table->dropUnique('pengajuan_user_unique');
            } catch (\Throwable $e) {
            }
            try {
                $table->dropForeign(['persetujuan_id']);
            } catch (\Throwable $e) {
            }
            // balikno kaya sakdurunge yen perlu (opsional)
        });
    }
};
