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
        Schema::create('dental_payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained('dental_appointments')->cascadeOnDelete();
            $table->foreignId('billing_id')->constrained('dental_appointment_billings')->cascadeOnDelete();
            $table->date('payment_date');
            $table->decimal('amount', 12, 2);
            $table->string('payment_mode', 50);
            $table->string('transaction_reference', 150)->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dental_payment_transactions');
    }
};
