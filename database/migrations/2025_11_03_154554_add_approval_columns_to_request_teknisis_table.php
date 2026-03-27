<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('request_teknisis', function (Blueprint $table) {
            // status final persetujuan oleh pemohon/superadmin
            $table->enum('final_status', ['pending', 'disetujui', 'ditolak'])
                  ->default('pending')
                  ->after('status');

            // kapan dan oleh siapa diputuskan
            $table->timestamp('finalized_at')->nullable()->after('final_status');
            $table->foreignId('finalized_by')->nullable()->after('finalized_at')
                  ->constrained('users')->nullOnDelete();

            // alasan penolakan (wajib saat ditolak)
            $table->text('rejection_reason')->nullable()->after('finalized_by');
        });
    }

    public function down(): void
    {
        Schema::table('request_teknisis', function (Blueprint $table) {
            $table->dropConstrainedForeignId('finalized_by');
            $table->dropColumn(['final_status', 'finalized_at', 'rejection_reason']);
        });
    }
};
