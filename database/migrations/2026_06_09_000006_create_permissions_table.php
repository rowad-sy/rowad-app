<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    * جدول الصلاحيات - قلب نظام الصلاحيات
    *
    * هذا الجدول يخزن الصلاحيات للمستخدمين والمجموعات.
    * الصلاحية تحدد: من لديه الصلاحية، على أي موديل، على أي مركز، على أي مشروع، ونوع الصلاحية.
    *
    * هيكل الصلاحية:
    * - user_id: المستخدم الذي لديه الصلاحية (إذا كانت الصلاحية لمستخدم محدد)
    * - group_id: المجموعة التي لديها الصلاحية (إذا كانت الصلاحية لمجموعة)
    * - model_name: اسم الموديل (الموديل) الذي تطبق عليه الصلاحية (مثال: App\Models\Center)
    * - model_id: ID محدد للموديل (إذا أردنا صلاحية على عنصر معين، اترك null للكل)
    * - center_id: نطاق المركز (null = جميع المراكز، قيمة = مركز محدد)
    * - project_id: نطاق المشروع (null = جميع المشاريع، قيمة = مشروع محدد)
    * - can_view, can_create, can_edit, can_delete: أنواع الصلاحيات
    *
    * ملاحظة للتوسيع: إذا أردت إضافة نطاق جديد (مثلاً قسم أو فرع)، أضف حقلاً جديداً (مثل department_id)
    * وإذا أردت إضافة نوع صلاحية جديد (مثل can_export)، أضف حقلاً جديداً (can_export boolean)
    * هذا يجعل النظام قابلاً للتوسيع بسهولة دون تغيير البنية الأساسية.
    */
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();

            // لمن تعطى الصلاحية (مستخدم محدد أو مجموعة)
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('group_id')->nullable()->constrained()->cascadeOnDelete();

            // على ماذا تطبق الصلاحية
            $table->string('model_name'); // اسم الموديل (App\Models\Center)
            $table->unsignedBigInteger('model_id')->nullable(); // عنصر محدد (null = الكل)

            // النطاق (scope)
            $table->foreignId('center_id')->nullable()->constrained()->cascadeOnDelete(); // null = جميع المراكز
            $table->foreignId('project_id')->nullable()->constrained()->cascadeOnDelete(); // null = جميع المشاريع

            // أنواع الصلاحيات
            $table->boolean('can_view')->default(false);
            $table->boolean('can_create')->default(false);
            $table->boolean('can_edit')->default(false);
            $table->boolean('can_delete')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
