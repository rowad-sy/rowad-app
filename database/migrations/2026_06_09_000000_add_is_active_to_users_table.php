<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    * إضافة حقل is_active لمستخدمي النظام
    * لتحديد ما إذا كان المستخدم فعالاً (نشط) أم غير فعال (موقوف)
    * المستخدم غير الفعال لا يمكنه تسجيل الدخول
    */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('remember_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
