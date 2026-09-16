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
        Schema::create('dental_teeth', function (Blueprint $table) {
            $table->id();
            $table->string('tooth_no', 10)->unique();
            $table->string('tooth_name', 100)->nullable();
            $table->string('tooth_type', 50)->nullable();
            $table->string('quadrant', 50)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dental_teeth');
    }
};
