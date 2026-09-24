<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
     * رمز فريد لكل موقع يُستخدم في استيراد/تصدير مجموعة التوقيعات
     * للربط بين الأسماء في الملفات وقاعدة البيانات.
     */
    public function up(): void
    {
        Schema::table('certificate_signers', function (Blueprint $table) {
            $table->string('code', 60)->nullable()->unique()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('certificate_signers', function (Blueprint $table) {
            $table->dropColumn('code');
        });
    }
};
