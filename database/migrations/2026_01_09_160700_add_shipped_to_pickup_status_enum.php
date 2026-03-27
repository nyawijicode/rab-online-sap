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
        // Laravel's enum update can be tricky, using raw SQL for MySQL/MariaDB
        DB::statement("ALTER TABLE pickups CHANGE COLUMN status status ENUM('scheduled', 'shipped', 'completed', 'canceled') NOT NULL DEFAULT 'scheduled'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE pickups CHANGE COLUMN status status ENUM('scheduled', 'completed', 'canceled') NOT NULL DEFAULT 'scheduled'");
    }
};
