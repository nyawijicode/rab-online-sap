<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qc_task_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qc_task_id')->constrained('qc_tasks')->onDelete('cascade');
            $table->foreignId('qc_criteria_id')->constrained('qc_criteria')->onDelete('cascade');
            $table->boolean('is_checked')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qc_task_criteria');
    }
};
