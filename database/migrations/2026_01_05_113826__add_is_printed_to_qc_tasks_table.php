<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('qc_tasks', function (Blueprint $table) {
            $table->boolean('is_printed')->default(false)->after('status');
        });

        // Migrate existing 'printed' status to 'completed' and set is_printed = true
        DB::table('qc_tasks')
            ->where('status', 'printed')
            ->update([
                'status' => 'completed',
                'is_printed' => true,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert 'completed' with is_printed = true back to 'printed' status
        DB::table('qc_tasks')
            ->where('status', 'completed')
            ->where('is_printed', true)
            ->update([
                'status' => 'printed',
            ]);

        Schema::table('qc_tasks', function (Blueprint $table) {
            $table->dropColumn('is_printed');
        });
    }
};
