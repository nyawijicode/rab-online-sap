<?php
// database/migrations/xxxx_xx_xx_xxxxxx_add_jenis_servis_to_services_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('services', function (Blueprint $table) {
            if (!Schema::hasColumn('services', 'jenis_servis')) {
                $table->enum('jenis_servis', ['paket','inventaris'])
                      ->default('paket')
                      ->after('id');
            }
        });
    }

    public function down(): void {
        Schema::table('services', function (Blueprint $table) {
            if (Schema::hasColumn('services', 'jenis_servis')) {
                $table->dropColumn('jenis_servis');
            }
        });
    }
};
