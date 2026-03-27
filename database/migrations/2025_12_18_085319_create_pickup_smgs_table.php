<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pickup_smgs', function (Blueprint $table) {
            $table->id();

            $table->date('tanggal_request');
            $table->string('nama_supplier', 200);
            $table->text('alamat_supplier')->nullable();

            $table->enum('status', ['pending', 'done'])->default('pending')->index();

            $table->json('personil')->nullable();
            $table->date('tanggal_pengambilan')->nullable();

            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('updated_by')->nullable()->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pickup_smgs');
    }
};
