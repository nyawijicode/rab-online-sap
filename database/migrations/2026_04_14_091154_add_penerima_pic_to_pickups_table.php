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
        Schema::table('pickups', function (Blueprint $table) {
            $table->string('penerima_pic_name')->nullable()->before('vendor_address');
            $table->string('penerima_pic_phone')->nullable()->before('vendor_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pickups', function (Blueprint $table) {
            $table->dropColumn(['penerima_pic_name', 'penerima_pic_phone']);
        });
    }
};
