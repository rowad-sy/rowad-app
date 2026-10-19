<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
     * رقم ترتيب المشروع في كل القوائم:
     * - 1 تظهر أولاً، ثم 2، ... إلخ
     * - NULL (لم يُحدَّد) تظهر آخر القائمة مرتبة بالاسم
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->integer('sort_order')->nullable()->after('code');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
