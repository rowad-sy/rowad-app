<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * طلب التصميم الإعلاني قد يخدم عدة مراكز معاً — جدول ربط بدل المركز الواحد.
 * عمود center_id يبقى «المركز الرئيسي» (الأول) للتوافق مع الفلاتر والقوائم القديمة.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_design_request_center', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_design_request_id')->constrained('ad_design_requests')->cascadeOnDelete();
            $table->foreignId('center_id')->constrained('centers')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['ad_design_request_id', 'center_id']);
        });

        foreach (DB::table('ad_design_requests')->whereNotNull('center_id')->cursor() as $row) {
            DB::table('ad_design_request_center')->insert([
                'ad_design_request_id' => $row->id,
                'center_id' => $row->center_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_design_request_center');
    }
};
