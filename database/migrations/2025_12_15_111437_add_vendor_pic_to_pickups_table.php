<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pickups', function (Blueprint $table) {
            $table->string('vendor_pic_name')->nullable()->after('vendor_address');
            $table->string('vendor_pic_phone')->nullable()->after('vendor_pic_name');
        });
    }

    public function down(): void
    {
        Schema::table('pickups', function (Blueprint $table) {
            $table->dropColumn([
                'vendor_pic_name',
                'vendor_pic_phone',
            ]);
        });
    }
};
