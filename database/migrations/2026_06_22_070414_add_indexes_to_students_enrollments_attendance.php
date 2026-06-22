<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->index('status');
            $table->index('gender');
        });

        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->index('enrollment_date');
        });

        Schema::table('attendance', function (Blueprint $table) {
            $table->index('date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['gender']);
        });

        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->dropIndex(['enrollment_date']);
        });

        Schema::table('attendance', function (Blueprint $table) {
            $table->dropIndex(['date']);
            $table->dropIndex(['status']);
        });
    }
};
