<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_plan_lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_level_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('instructor_id')->nullable()->constrained('hr_employees')->nullOnDelete();
            $table->unsignedInteger('week_number');
            $table->unsignedSmallInteger('day_of_week'); // 1=Saturday..7=Friday (ISO) -> configurable
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('lesson_name', 200)->nullable();
            $table->string('location', 200)->nullable();
            $table->timestamps();

            $table->index(['training_plan_id', 'academic_level_id']);
            $table->index(['instructor_id', 'day_of_week', 'start_time', 'end_time'], 'idx_lesson_conflict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_plan_lessons');
    }
};
