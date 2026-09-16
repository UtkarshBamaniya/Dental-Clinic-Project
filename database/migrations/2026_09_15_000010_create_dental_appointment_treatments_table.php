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
        Schema::create('dental_appointment_treatments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained('dental_appointments')->cascadeOnDelete();
            $table->foreignId('treatment_id')->constrained('dental_treatments')->restrictOnDelete();
            $table->string('tooth_no', 10)->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('status', 20)->default('planned');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dental_appointment_treatments');
    }
};
