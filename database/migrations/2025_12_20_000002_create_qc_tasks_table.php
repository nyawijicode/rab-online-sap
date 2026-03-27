<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qc_tasks', function (Blueprint $table) {
            $table->id();
            $table->integer('doc_entry');
            $table->string('qc_no');
            $table->integer('base_line_id');
            $table->string('item_code');
            $table->string('item_name');
            $table->decimal('qty', 18, 4);
            $table->string('serial_number')->nullable();

            $table->foreignId('technician_id')->constrained('users');
            $table->foreignId('coordinator_id')->constrained('users');

            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->string('status')->default('pending'); // pending, completed

            $table->decimal('qty_pass', 18, 4)->default(0);
            $table->decimal('qty_fail', 18, 4)->default(0);
            $table->string('condition')->nullable(); // ok, broken
            $table->text('reason')->nullable();
            $table->string('scanned_serial_number')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qc_tasks');
    }
};
