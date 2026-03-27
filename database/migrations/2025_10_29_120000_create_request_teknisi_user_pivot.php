<?php
// database/migrations/2025_10_29_120000_create_request_teknisi_user_pivot.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('request_teknisi_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_teknisi_id')
                ->constrained('request_teknisis')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['request_teknisi_id','user_id'], 'rtu_unique');
        });

        // Backfill: copy nilai legacy teknisi_id ke pivot kalau ada
        // (Aman untuk server yang sudah berdata)
        DB::statement("
            INSERT INTO request_teknisi_user (request_teknisi_id, user_id, created_at, updated_at)
            SELECT id AS request_teknisi_id, teknisi_id AS user_id, NOW(), NOW()
            FROM request_teknisis
            WHERE teknisi_id IS NOT NULL
              AND NOT EXISTS (
                    SELECT 1 FROM request_teknisi_user rtu
                    WHERE rtu.request_teknisi_id = request_teknisis.id
                      AND rtu.user_id = request_teknisis.teknisi_id
              )
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('request_teknisi_user');
    }
};
