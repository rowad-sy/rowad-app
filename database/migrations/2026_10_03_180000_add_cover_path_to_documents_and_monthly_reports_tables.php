<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * غلاف (cover) لكل وثيقة مشروع وتقارير شهرية: صورة A4 عمودية تُطبع
 * كأول صفحة كاملة قبل المحتوى — الشعار مدموج داخل الصورة نفسها،
 * لذا صفحة الغلاف لا تُطبع فوقها ترويسة الشعار.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('annex_documents', function (Blueprint $table) {
            $table->string('cover_path')->nullable()->after('data');
        });

        Schema::table('monthly_reports', function (Blueprint $table) {
            $table->string('cover_path')->nullable()->after('data');
        });
    }

    public function down(): void
    {
        Schema::table('annex_documents', function (Blueprint $table) {
            $table->dropColumn('cover_path');
        });

        Schema::table('monthly_reports', function (Blueprint $table) {
            $table->dropColumn('cover_path');
        });
    }
};
