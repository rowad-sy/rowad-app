<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    * جدول مجموعات المستخدمين
    * يستخدم لتجميع المستخدمين في مجموعات صلاحيات
    * مثال: hr_officers, admins, viewers
    * هذا يسهل إدارة الصلاحيات حيث يمكن إعطاء صلاحيات لمجموعة بدلاً من كل مستخدم على حدة
    */
    public function up(): void
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // اسم المجموعة (مثال: مسؤولي الموارد البشرية)
            $table->text('description')->nullable(); // وصف المجموعة ودورها
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('groups');
    }
};
