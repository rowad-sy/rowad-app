<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * دورة روادنا على مستوى الخطة:
 *   review (مدير مباشر) → manager_approved (مدير مشاريع) → pm2_review للحالات
 *   التي أنشأها مدير المشروع نفسه (بلا مدير مباشر) → rowaduna_review (عهدة
 *   مسؤول روادنا) → in_progress (إُسندت الفعاليات) → executed | rejected.
 * الأعمدة القديمة refer_to_media_manager_id / refer_to_media_officer_id تبقى
 * للتاريخ فقط ولا تُستخدم في الدورة الجديدة.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media_plans', function (Blueprint $table) {
            $table->foreignId('refer_to_rowaduna_id')->nullable()->constrained('users')->nullOnDelete()->after('refer_to_pm2_id');
        });

        Schema::table('media_plan_events', function (Blueprint $table) {
            $table->string('coverage_status')->default('pending')->after('notes');
            $table->foreignId('refer_to_reporter_id')->nullable()->constrained('users')->nullOnDelete()->after('coverage_status');
            $table->text('not_covered_reason')->nullable()->after('refer_to_reporter_id');
            $table->text('coverage_note')->nullable()->after('not_covered_reason');
            $table->string('media_items_url')->nullable()->after('coverage_note');

            $table->string('publish_status')->default('none')->after('media_items_url');
            $table->foreignId('refer_to_publisher_id')->nullable()->constrained('users')->nullOnDelete()->after('publish_status');
            $table->string('preview_url')->nullable()->after('refer_to_publisher_id');
            $table->foreignId('refer_to_reviewer_id')->nullable()->constrained('users')->nullOnDelete()->after('preview_url');
            $table->text('preview_feedback')->nullable()->after('refer_to_reviewer_id');
            $table->json('publish_links')->nullable()->after('preview_feedback');
            $table->foreignId('preview_reviewed_by')->nullable()->constrained('users')->nullOnDelete()->after('publish_links');
            $table->timestamp('preview_reviewed_at')->nullable()->after('preview_reviewed_by');
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete()->after('preview_reviewed_at');
            $table->timestamp('published_at')->nullable()->after('published_by');

            $table->index(['refer_to_reporter_id', 'event_date', 'event_time'], 'media_events_reporter_slot');
        });
    }

    public function down(): void
    {
        Schema::table('media_plan_events', function (Blueprint $table) {
            $table->dropIndex('media_events_reporter_slot');
            foreach ([
                'refer_to_reporter_id', 'refer_to_publisher_id', 'refer_to_reviewer_id',
                'preview_reviewed_by', 'published_by',
            ] as $fk) {
                $table->dropConstrainedForeignId($fk);
            }
            $table->dropColumn([
                'coverage_status', 'not_covered_reason', 'coverage_note', 'media_items_url',
                'publish_status', 'preview_url', 'preview_feedback', 'publish_links',
                'preview_reviewed_at', 'published_at',
            ]);
        });

        Schema::table('media_plans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('refer_to_rowaduna_id');
        });
    }
};
