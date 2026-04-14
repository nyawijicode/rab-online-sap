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
        Schema::table('pickup_items', function (Blueprint $table) {
            $table->string('unit', 50)->nullable()->after('pickup_quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pickup_items', function (Blueprint $table) {
            $table->dropColumn('unit');
        });
    }
};
