<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * طلب تصميم إعلاني (دورات/أنشطة):
 *   مدير المشروع ينشئ → pm2_review (مدير المشاريع) → rowaduna_review (مسؤول
 *   روادنا) → designing (المصمم يرفع رابط التصميم) → ready_for_review (مدير
 *   المشروع يراجع الرابط: موافقة أو إعادة تنفيذ) → to_publish (الناشر يدخل
 *   روابط النشر) → published | rejected.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_design_requests', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('center_id')->nullable()->constrained('centers')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('due_date')->nullable();
            $table->string('status')->default('pm2_review');

            $table->foreignId('refer_to_pm2_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('refer_to_rowaduna_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('refer_to_designer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('refer_to_publisher_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('design_url')->nullable();
            $table->text('design_note')->nullable();
            $table->text('revision_note')->nullable();
            $table->json('publish_links')->nullable();

            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_by']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_design_requests');
    }
};
