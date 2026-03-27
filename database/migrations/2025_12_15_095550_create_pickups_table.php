<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pickups', function (Blueprint $table) {
            $table->id();

            // PO dari SAP
            $table->unsignedBigInteger('po_docentry')->index();
            $table->string('po_number')->index(); // DocNum

            // Vendor dari PO (OCRD)
            $table->string('vendor_code')->index();
            $table->string('vendor_name');

            // Jadwal pickup
            $table->date('pickup_date');
            $table->string('pickup_day')->index(); // auto dari pickup_date
            $table->unsignedInteger('pickup_duration'); // menit (atau satuan kamu)

            // Ekspedisi (OCRD CardCode LIKE 'VE%')
            $table->string('expedition_supplier_code')->index();
            $table->string('expedition_supplier_name');

            $table->text('notes')->nullable();

            // Status
            $table->enum('status', ['scheduled', 'completed', 'canceled'])->default('scheduled')->index();

            $table->timestamps();

            // Optional: biar tidak dobel record PO sama persis (boleh)
            // kalau kamu mau boleh aktifkan:
            // $table->unique(['po_docentry']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pickups');
    }
};
