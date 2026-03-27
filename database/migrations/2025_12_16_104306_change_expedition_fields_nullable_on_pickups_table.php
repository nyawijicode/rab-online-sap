<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pickups', function (Blueprint $table) {
            $table->string('expedition_supplier_code')->nullable()->change();
            $table->string('expedition_supplier_name')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('pickups', function (Blueprint $table) {
            $table->string('expedition_supplier_code')->nullable(false)->change();
            $table->string('expedition_supplier_name')->nullable(false)->change();
        });
    }
};
