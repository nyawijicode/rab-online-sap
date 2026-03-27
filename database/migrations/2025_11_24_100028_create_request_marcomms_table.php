<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('request_marcomms', function (Blueprint $table) {
            $table->id();

            // nomor request
            $table->string('no_request')->unique();

            // relasi pemohon
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // informasi pemohon / kontak
            $table->string('nama_pemohon');
            $table->string('jabatan')->nullable();
            $table->string('kantor_cabang')->nullable();
            $table->string('nama_atasan')->nullable();
            $table->string('nomor_kantor')->nullable();
            $table->string('email')->nullable();

            // kebutuhan (boleh lebih dari satu) -> JSON array enum values
            $table->json('kebutuhan')->nullable();

            // quantity global
            $table->unsignedInteger('quantity')->default(0);

            // foto referensi (boleh lebih dari satu) -> json path
            $table->json('foto')->nullable();

            // tanggal respon marcomm
            $table->date('tanggal_respon')->nullable();

            // status request enum disimpan sebagai string
            $table->string('status')->default('tunggu');

            // tanggal paket dikirim/diterima
            $table->date('tanggal_terkirim')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_marcomms');
    }
};
