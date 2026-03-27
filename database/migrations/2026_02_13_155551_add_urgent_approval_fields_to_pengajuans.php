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
        Schema::table('pengajuans', function (Blueprint $table) {
            $table->boolean('urgent_approved')->nullable()->after('urgent_proof_path');
            $table->unsignedBigInteger('urgent_approved_by')->nullable()->after('urgent_approved');
            $table->timestamp('urgent_approved_at')->nullable()->after('urgent_approved_by');
            $table->text('urgent_approval_reason')->nullable()->after('urgent_approved_at');

            // Foreign key
            $table->foreign('urgent_approved_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            $table->dropForeign(['urgent_approved_by']);
            $table->dropColumn(['urgent_approved', 'urgent_approved_by', 'urgent_approved_at', 'urgent_approval_reason']);
        });
    }
};
