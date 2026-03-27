<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pickup_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pickup_id')
                ->constrained('pickups')
                ->cascadeOnDelete();

            // referensi dari POR1 (optional, buat tracking)
            $table->unsignedInteger('line_num')->nullable();

            $table->string('item_code')->index();
            $table->string('description')->nullable();

            // qty asli di PO
            $table->decimal('po_quantity', 19, 6)->nullable();

            // qty pickup (manual)
            $table->decimal('pickup_quantity', 19, 6);

            $table->timestamps();

            // optional safety: item tidak boleh dobel untuk pickup yang sama
            $table->unique(['pickup_id', 'item_code'], 'uq_pickup_items_pickup_item');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pickup_items');
    }
};
