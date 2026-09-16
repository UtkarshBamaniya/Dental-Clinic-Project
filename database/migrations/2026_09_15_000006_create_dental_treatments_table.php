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
        Schema::create('dental_treatments', function (Blueprint $table) {
            $table->id();
            $table->string('treatment_code', 50)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->decimal('default_price', 10, 2)->default(0);
            $table->integer('duration_minutes')->default(30);
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
        Schema::dropIfExists('dental_treatments');
    }
};
