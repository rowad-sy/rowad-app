<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    * الترتيبة v2 (قسم 3 من docs/permission-v2-study.md):
    * التمييز بين «مجموعة» (حزمة صلاحيات لتجميع مستخدمين متشابهين — بلا نطاق)
    * و«دور» (وظيفة مسماة تُسند مع نطاق مركز/مشروع/فوج).
    *
    * الجدول واحد والمنطق واحد — الفرق سلوك إداري فقط، لذا نكتلع بعمود kind
    * بدلاً من جدول مستقل (قرار الدراسة: الخيار أ).
    *
    * الاعتماد على جداول HR ممنوع في الترتيبة الجديدة — لا شيء هنا يلمسها.
    */
    public function up(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->string('kind', 10)->default('group')->after('name'); // group | role
        });
    }

    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn('kind');
        });
    }
};
