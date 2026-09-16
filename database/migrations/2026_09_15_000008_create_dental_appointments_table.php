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
        Schema::create('dental_appointments', function (Blueprint $table) {
            $table->id();

            $table->string('appointment_no', 30)->unique();

            $table->foreignId('patient_id')->constrained('dental_patients')->cascadeOnDelete();
            
            $table->foreignId('doctor_id')->nullable()->constrained('dental_doctors')->nullOnDelete();
            
            $table->foreignId('chair_id')->nullable()->constrained('dental_chairs')->nullOnDelete();
            
            $table->foreignId('appointment_type_id')->nullable()->constrained('dental_appointment_types')->nullOnDelete();

            $table->date('appointment_date');
            
            $table->time('appointment_time');

            $table->string('visit_type', 30);

            $table->text('chief_complaint')->nullable();

            $table->string('problem_area', 100)->nullable();

            $table->string('tooth_no', 100)->nullable();

            $table->string('priority', 30)->default('normal');

            $table->string('status', 30)->default('scheduled');

            $table->foreignId('previous_appointment_id')->nullable()->constrained('dental_appointments')->nullOnDelete();

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dental_appointments');
    }
};
