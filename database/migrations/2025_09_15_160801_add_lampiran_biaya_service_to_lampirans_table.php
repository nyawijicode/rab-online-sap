<?php

// database/migrations/xxxx_xx_xx_xxxxxx_add_lampiran_biaya_service_to_lampirans_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('lampirans', function (Blueprint $table) {
            // boolean di MySQL = tinyint(1)
            $table->boolean('lampiran_biaya_service')
                ->default(false)
                ->after('lampiran_asset')
                ->comment('Flag ada/tidak lampiran biaya service');
        });
    }

    public function down(): void
    {
        Schema::table('lampirans', function (Blueprint $table) {
            $table->dropColumn('lampiran_biaya_service');
        });
    }
};
