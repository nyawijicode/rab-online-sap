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
        Schema::create('pengajuan_so_pls', function (Blueprint $table) {
            $table->id();

            $table->string('nama_dinas', 191);
            $table->string('upload_file_rab', 255)->nullable();
            $table->string('upload_file_sp', 255)->nullable();
            $table->string('nama_pic', 191)->nullable();
            $table->string('nomor_pic', 50)->nullable();
            $table->string('upload_file_npwp', 255)->nullable();
            $table->text('alamat_pengiriman')->nullable();
            $table->text('keterangan')->nullable();
            $table->string('no_so_pl', 100)->nullable();
            $table->dateTime('tanggal_respon')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengajuan_so_pls');
    }
};
