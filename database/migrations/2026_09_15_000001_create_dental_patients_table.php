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
        Schema::create('dental_patients', function (Blueprint $table) {
            $table->id();

            $table->string('patient_code', 30)->unique();

            $table->string('first_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();

            $table->string('gender', 20)->nullable();
            $table->date('date_of_birth')->nullable();

            $table->string('mobile', 20);
            $table->string('alternate_mobile', 20)->nullable();
            $table->string('email', 150)->nullable();

            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('pincode', 20)->nullable();

            $table->string('occupation', 100)->nullable();
            $table->string('referred_by', 150)->nullable();

            $table->string('status', 20)->default('active');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dental_patients');
    }
};
