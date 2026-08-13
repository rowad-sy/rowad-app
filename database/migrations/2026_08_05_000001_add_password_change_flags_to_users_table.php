<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    * إضافة حقول فرض تغيير كلمة المرور وإرسال بريد التفعيل
    * must_change_password: هل يجب على المستخدم تغيير كلمة المرور عند تسجيل الدخول؟
    * activation_email_sent_at: متى تم إرسال بريد التفعيل للمستخدم؟
    */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('must_change_password')->default(false)->after('is_active');
            $table->timestamp('activation_email_sent_at')->nullable()->after('must_change_password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['must_change_password', 'activation_email_sent_at']);
        });
    }
};
