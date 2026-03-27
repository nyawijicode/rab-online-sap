<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lampiran_biaya_services', function (Blueprint $table) {
            $table->id();

            // relasi ke pengajuans.id (sama seperti contohmu)
            $table->foreignId('pengajuan_id')->constrained()->cascadeOnDelete();

            $table->string('file_path');      // path file di storage (mis. storage/app/public/...)
            $table->string('original_name');  // nama asli file saat diupload

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lampiran_biaya_services');
    }
};
