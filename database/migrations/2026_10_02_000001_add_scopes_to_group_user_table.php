<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    * نقل النطاق إلى الإسناد (مقترح §17 — الأدوار Roles)
    *
    * مجموعة = قالب دور (الموديلات والأعلام فقط).
    * العضوية نفسها (group_user) هي التي تحدد اين يسري هذا الدور:
    * center_id / project_id / cohort_id — والقيمة null تعني "كل ما يلي".
    *
    * التوافق الخلفي: سجلات permissions القديمة الخاصة بالمجموعة والتي تحمل
    * نطاقاً تبقى فعالة كما هي، ولا تنكسر أي عضوية موجودة (الأعمدة nullable).
    */
    public function up(): void
    {
        Schema::table('group_user', function (Blueprint $table) {
            $table->foreignId('center_id')->nullable()->after('user_id')
                ->constrained('centers')->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->after('center_id')
                ->constrained('projects')->cascadeOnDelete();
            $table->foreignId('cohort_id')->nullable()->after('project_id')
                ->constrained('cohorts')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('group_user', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cohort_id');
            $table->dropConstrainedForeignId('project_id');
            $table->dropConstrainedForeignId('center_id');
        });
    }
};
