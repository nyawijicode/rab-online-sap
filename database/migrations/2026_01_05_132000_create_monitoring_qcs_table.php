<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitoring_qcs', function (Blueprint $table) {
            $table->id();
            $table->integer('doc_entry')->nullable();
            $table->string('qc_no')->nullable();
            $table->string('item_code')->nullable();
            $table->text('item_name')->nullable();
            $table->integer('qty')->default(0);
            $table->string('task_no')->nullable();
            $table->string('technician')->nullable();
            $table->string('status')->nullable();
            $table->boolean('is_printed')->default(false);
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();

            $table->index(['doc_entry', 'status']);
            $table->index('task_no');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitoring_qcs');
    }
};
