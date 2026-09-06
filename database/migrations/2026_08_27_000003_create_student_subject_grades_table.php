<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_subject_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_enrollment_id')->constrained('student_enrollments')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->decimal('grade', 5, 2)->nullable();
            $table->timestamps();

            $table->unique(['student_enrollment_id', 'subject_id'], 'uq_student_enrollment_subject');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_subject_grades');
    }
};
