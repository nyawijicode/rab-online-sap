<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('request_marcomms', function (Blueprint $table) {
            $table->unsignedBigInteger('companies_id')
                ->after('id')
                ->default(1);
        });

        // Pastikan data lama aman
        DB::table('request_marcomms')
            ->whereNull('companies_id')
            ->update(['companies_id' => 1]);

        Schema::table('request_marcomms', function (Blueprint $table) {
            $table->foreign('companies_id')
                ->references('id')
                ->on('companies')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('request_marcomms', function (Blueprint $table) {
            $table->dropForeign(['companies_id']);
            $table->dropColumn('companies_id');
        });
    }
};
