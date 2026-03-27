<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('request_teknisis', function (Blueprint $table) {
            $table->id();

            // nomor otomatis N+1 (dibikin di model)
            $table->string('no_request')->unique();

            // mengikuti Service: id_paket dari project.code, nama_dinas = customer.name
            $table->string('id_paket');
            $table->string('nama_dinas');

            $table->string('nama_kontak');
            $table->string('no_telepon');

            $table->enum('jenis_pekerjaan', ['Onsite Service','Uji Fungsi','Instalasi','Survey','Visit']);

            $table->string('cabang')->nullable();

            $table->date('tanggal_pelaksanaan')->nullable();

            // Hanya koordinator/superadmin yang isi
            $table->date('tanggal_penjadwalan')->nullable();
            $table->foreignId('teknisi_id')->nullable()->constrained('users')->nullOnDelete();

            $table->enum('status', ['request','penjadwalan','progres','selesai','ditolak'])->default('request');

            $table->text('keterangan')->nullable();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_teknisis');
    }
};
