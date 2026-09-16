<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('dental_patient_tooth_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('dental_patients')->cascadeOnDelete();
            $table->foreignId('tooth_id')->constrained('dental_teeth')->restrictOnDelete();
            $table->string('condition', 100)->nullable();
            $table->string('status', 50)->nullable();
            $table->text('notes')->nullable();
            $table->date('recorded_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dental_patient_tooth_records');
    }
};
