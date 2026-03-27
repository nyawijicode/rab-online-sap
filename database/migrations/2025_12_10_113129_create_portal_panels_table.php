<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portal_panels', function (Blueprint $table) {
            $table->id();

            $table->string('name');          // Nama yang tampil di kartu, contoh: "Panel RAB"
            $table->string('code')->unique(); // Kode/slug, contoh: "rab"
            $table->string('url');           // URL path, contoh: "/rab"
            $table->string('badge')->nullable(); // Keterangan singkat di kanan atas, contoh: "Keuangan / Marcomm"
            $table->text('description')->nullable(); // Deskripsi singkat

            $table->boolean('is_active')->default(true); // aktif / nonaktif
            $table->integer('sort_order')->nullable();   // urutan tampilan (semakin kecil, semakin atas)

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_panels');
    }
};
