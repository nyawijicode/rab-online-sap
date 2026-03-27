<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cocokan_faktur_pajaks', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_faktur')->index();
            $table->string('nama_vendor')->nullable();
            $table->boolean('ada_di_coretax')->default(false);
            $table->boolean('ada_di_sap')->default(false);
            $table->boolean('status_cocok')->default(false);
            $table->date('first_appeared_at');
            $table->date('resolved_at')->nullable();
            $table->string('periode_minggu')->index(); // e.g. "2026-W11"
            $table->string('periode_bulan')->index();  // e.g. "2026-01"
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cocokan_faktur_pajaks');
    }
};
