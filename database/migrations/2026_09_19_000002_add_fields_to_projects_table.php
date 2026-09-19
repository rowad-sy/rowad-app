<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
     * إضافة حقول المشاريع الجديدة:
     * - path_id: المسار الذي ينتمي إليه المشروع
     * - status: حالة المشروع (فعال / تحت الدراسة / مغلق / معلق / داخلي)
     * - code: كود المشروع
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('path_id')->nullable()->after('description')
                ->constrained('project_paths')->nullOnDelete();
            $table->string('status')->default('active')->after('path_id');
            $table->string('code')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('path_id');
            $table->dropColumn(['status', 'code']);
        });
    }
};
