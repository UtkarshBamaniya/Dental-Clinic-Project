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
        Schema::create('dental_patient_medical_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->unique()->constrained('dental_patients')->cascadeOnDelete();
            $table->string('blood_group', 10)->nullable();
            $table->text('current_medicine')->nullable();
            $table->text('previous_dental_treatment')->nullable();
            $table->text('other_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dental_patient_medical_histories');
    }
};
