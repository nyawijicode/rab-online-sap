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
        Schema::table('persetujuans', function (Blueprint $table) {
            $table->string('company')->nullable()->default('sap')->after('user_id');
        });

        // Update existing records to have company = 'sap'
        DB::table('persetujuans')->update(['company' => 'sap']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('persetujuans', function (Blueprint $table) {
            $table->dropColumn('company');
        });
    }
};
