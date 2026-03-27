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
        Schema::table('persetujuan_approvers', function (Blueprint $table) {
            $table->dropColumn('role');
            $table->foreignId('divisi_id')->nullable()->after('approver_id')->constrained('divisis')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('persetujuan_approvers', function (Blueprint $table) {
            $table->dropForeign(['divisi_id']);
            $table->dropColumn('divisi_id');
            $table->string('role')->nullable()->after('approver_id');
        });
    }
};
