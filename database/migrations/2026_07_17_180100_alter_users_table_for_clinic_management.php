<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('phone')->nullable()->after('email');
            $table->string('role')->default('receptionist')->after('phone');
            $table->string('job_title')->nullable()->after('role');
            $table->boolean('status')->default(true)->after('job_title');
            $table->decimal('monthly_salary', 12, 2)->default(0)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
            $table->dropColumn(['phone', 'role', 'job_title', 'status', 'monthly_salary']);
        });
    }
};
