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
            $table->string('item_code')->nullable()->change();
            $table->decimal('pickup_quantity', 15, 6)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pickup_items', function (Blueprint $table) {
            $table->string('item_code')->nullable(false)->change();
            $table->decimal('pickup_quantity', 15, 6)->nullable(false)->change();
        });
    }
};
