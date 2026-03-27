<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // pastikan tabel pickups ada
        if (! Schema::hasTable('pickups')) {
            return;
        }

        Schema::table('pickups', function (Blueprint $table) {
            // created_by
            if (! Schema::hasColumn('pickups', 'created_by')) {
                $table->foreignId('created_by')
                    ->nullable()
                    ->after('id')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            // updated_by
            if (! Schema::hasColumn('pickups', 'updated_by')) {
                $table->foreignId('updated_by')
                    ->nullable()
                    ->after('created_by')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('pickups')) {
            return;
        }

        Schema::table('pickups', function (Blueprint $table) {
            // drop foreign key & column updated_by
            if (Schema::hasColumn('pickups', 'updated_by')) {
                try {
                    $table->dropForeign(['updated_by']);
                } catch (\Throwable $e) {
                    // ignore
                }

                $table->dropColumn('updated_by');
            }

            // drop foreign key & column created_by
            if (Schema::hasColumn('pickups', 'created_by')) {
                try {
                    $table->dropForeign(['created_by']);
                } catch (\Throwable $e) {
                    // ignore
                }

                $table->dropColumn('created_by');
            }
        });
    }
};
