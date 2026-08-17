<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('doctor_profiles', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->nullable()->default(0);
            $table->tinyInteger('branch_id')->default(0);
            $table->string('specialty');
            $table->string('room_number')->nullable();
            $table->decimal('consultation_fee', 12, 2)->default(0);
            $table->string('theme_color')->default('#0f766e');
            $table->boolean('is_available')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_profiles');
    }
};
