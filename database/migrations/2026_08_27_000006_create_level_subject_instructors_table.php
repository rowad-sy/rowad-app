<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('level_subject_instructors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_level_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('instructor_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->boolean('is_main')->default(false);
            $table->timestamps();

            $table->unique(['academic_level_id', 'subject_id', 'instructor_id'], 'uq_level_subject_instructor');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('level_subject_instructors');
    }
};
