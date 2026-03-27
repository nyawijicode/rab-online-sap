<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pickups', function (Blueprint $table) {
            $table->string('no_resi')->nullable()->after('status');

            // jangka waktu pelaksanaan (input manual, boleh "3 hari", "2 minggu", dll)
            $table->date('jangka_waktu_pelaksanaan')->nullable()->after('no_resi');

            // tagihan ke: sap / ssm / dll (input manual)
            $table->string('tagihan_ke')->nullable()->after('jangka_waktu_pelaksanaan');

            // pengambilan cabang (manual)
            $table->string('pengambilan_cabang')->nullable()->after('tagihan_ke');

            // tujuan pengiriman (manual)
            $table->string('tujuan_pengiriman')->nullable()->after('pengambilan_cabang');

            // alamat dropship (manual, bisa panjang)
            $table->text('alamat_dropship')->nullable()->after('tujuan_pengiriman');
        });
    }

    public function down(): void
    {
        Schema::table('pickups', function (Blueprint $table) {
            $table->dropColumn([
                'no_resi',
                'jangka_waktu_pelaksanaan',
                'tagihan_ke',
                'pengambilan_cabang',
                'tujuan_pengiriman',
                'alamat_dropship',
            ]);
        });
    }
};
