<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    * ربط الموظف بالفوج — لكي يحدد الميدل وير نطاق الفوج الخاص بالمستخدم الحالي.
    */
    public function up(): void
    {
        Schema::table('hr_employees', function (Blueprint $table) {
            $table->foreignId('cohort_id')->nullable()->after('project_id')
                ->constrained('cohorts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('hr_employees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cohort_id');
        });
    }
};
