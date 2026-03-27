<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pickups', function (Blueprint $table) {
            if (! Schema::hasColumn('pickups', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('pickups', function (Blueprint $table) {
            if (Schema::hasColumn('pickups', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
