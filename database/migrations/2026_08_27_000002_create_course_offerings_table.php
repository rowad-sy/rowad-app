<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_offerings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('instructor_id')->nullable()->constrained('hr_employees')->nullOnDelete();
            $table->string('name_ar', 200)->nullable();
            $table->string('session_time', 100)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->integer('capacity')->nullable();
            $table->enum('status', ['planned', 'active', 'completed', 'cancelled'])->default('planned');
            $table->timestamps();
            $table->softDeletes();

            $table->index('course_id');
            $table->index('period_id');
            $table->index('instructor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_offerings');
    }
};
