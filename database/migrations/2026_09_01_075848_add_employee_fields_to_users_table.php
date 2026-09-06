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
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('job_title_id')->nullable()->after('type');
            $table->unsignedBigInteger('center_id')->nullable()->after('job_title_id');
            $table->unsignedBigInteger('project_id')->nullable()->after('center_id');

            $table->foreign('job_title_id')->references('id')->on('hr_job_positions')->nullOnDelete();
            $table->foreign('center_id')->references('id')->on('centers')->nullOnDelete();
            $table->foreign('project_id')->references('id')->on('projects')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['job_title_id']);
            $table->dropForeign(['center_id']);
            $table->dropForeign(['project_id']);
            $table->dropColumn(['project_id', 'center_id', 'job_title_id']);
        });
    }
};
