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
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['token_no', 'estimated_amount', 'paid_amount', 'visit_type']);
            
            $table->foreignId('appointment_type_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('parent_appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['appointment_type_id']);
            $table->dropForeign(['parent_appointment_id']);
            $table->dropColumn(['appointment_type_id', 'parent_appointment_id']);
            
            $table->unsignedInteger('token_no')->default(1);
            $table->decimal('estimated_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->string('visit_type')->default('consultation');
        });
    }
};
