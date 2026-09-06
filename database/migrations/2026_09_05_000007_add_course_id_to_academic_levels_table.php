<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academic_levels', function (Blueprint $table) {
            $table->foreignId('course_id')
                ->nullable()
                ->after('project_id')
                ->constrained('courses')
                ->nullOnDelete();

            $table->index('course_id');
        });
    }

    public function down(): void
    {
        Schema::table('academic_levels', function (Blueprint $table) {
            $table->dropForeign(['course_id']);
            $table->dropIndex(['course_id']);
            $table->dropColumn('course_id');
        });
    }
};