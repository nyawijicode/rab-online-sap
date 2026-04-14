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
            $table->foreignId('cabang_id')->nullable()->after('pengambilan_cabang')->constrained('cabangs')->nullOnDelete();
            $table->foreignId('cabang_pic_user_id')->nullable()->after('cabang_pic_name')->constrained('users')->nullOnDelete();
        });

        // Data Migration: Map existing pengambilan_cabang (string) to cabang_id (ID)
        $cabangs = DB::table('cabangs')->pluck('id', 'kode')->all();
        foreach ($cabangs as $kode => $id) {
            DB::table('pickups')
                ->where('pengambilan_cabang', $kode)
                ->whereNull('cabang_id')
                ->update(['cabang_id' => $id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pickups', function (Blueprint $table) {
            $table->dropForeign(['cabang_id']);
            $table->dropColumn('cabang_id');
            $table->dropForeign(['cabang_pic_user_id']);
            $table->dropColumn('cabang_pic_user_id');
        });
    }
};
