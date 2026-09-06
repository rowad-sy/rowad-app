<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    * طبقة "الفوج" (Cohort)
    * =====================
    * الفوج مجموعة طلاب داخل مشروع (مثال: فوج صباحي / فوج مسائي في الروضة).
    * - مرتبط بالمشروع فقط (لا يتكرر بين المراكز).
    * - لكل فوج مسؤول عادةً شخص مختلف.
    * - يُستخدم كنطاق (scope) إضافي في نظام الصلاحيات إلى جانب المركز والمشروع.
    */
    public function up(): void
    {
        Schema::create('cohorts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            // مسؤول الفوج (اختياري) — موظف
            $table->foreignId('manager_id')->nullable()->constrained('hr_employees')->nullOnDelete();
            $table->string('name', 200);
            $table->string('shift', 50)->nullable(); // صباحي/مسائي
            $table->string('code', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('project_id');
        });

        // ربط الطالب بالفوج
        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('cohort_id')->nullable()->after('project_id')
                ->constrained('cohorts')->nullOnDelete();
        });

        // نطاق الفوج في الصلاحيات (بجانب المركز والمشروع)
        Schema::table('permissions', function (Blueprint $table) {
            $table->foreignId('cohort_id')->nullable()->after('project_id')
                ->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cohort_id');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cohort_id');
        });

        Schema::dropIfExists('cohorts');
    }
};
