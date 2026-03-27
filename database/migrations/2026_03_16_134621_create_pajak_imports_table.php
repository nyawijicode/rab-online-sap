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
        Schema::create('pajak_imports', function (Blueprint $table) {
            $table->id();
            $table->string('npwp_penjual')->nullable();
            $table->string('nama_penjual')->nullable();
            $table->string('nomor_faktur_pajak')->nullable();
            $table->date('tanggal_faktur_pajak')->nullable();
            $table->string('masa_pajak')->nullable();
            $table->string('tahun')->nullable();
            $table->string('masa_pajak_pengkreditan')->nullable();
            $table->string('tahun_pajak_pengkreditan')->nullable();
            $table->string('status_faktur')->nullable();
            $table->decimal('harga_jual_dpp', 18, 2)->default(0);
            $table->decimal('dpp_nilai_lain', 18, 2)->default(0);
            $table->decimal('ppn', 18, 2)->default(0);
            $table->decimal('ppnbm', 18, 2)->default(0);
            $table->string('perekam')->nullable();
            $table->string('referensi')->nullable();
            $table->string('nomor_sp2d')->nullable();
            $table->string('valid')->nullable();
            $table->string('dilaporkan')->nullable();
            $table->string('dilaporkan_oleh_penjual')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pajak_imports');
    }
};
