<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
     * بطاقة فعالية — نموذج مستقل عن الخطة الإعلامية.
     * ينشئها مدير المشروع ويحيلها لشخص واحد (قائمة منسدلة) يوافق عليها،
     * ثم تُعتمد من مدير المشاريع. الحقول التفصيلية (الفقرات/اللوجستيات/
     * المشتريات/الإعلام/المواصلات/الموازنة) مخزنة كـ JSON.
     */
    public function up(): void
    {
        Schema::create('event_cards', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // اسم الفعالية
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('center_id')->nullable()->constrained()->nullOnDelete();
            $table->date('event_date')->nullable();
            $table->string('location')->nullable(); // المكان
            $table->string('organizer')->nullable(); // المنظم
            $table->string('presenter')->nullable(); // مقدم الحفل
            $table->unsignedInteger('expected_attendance')->nullable(); // عدد الحضور المتوقع
            $table->text('objectives')->nullable(); // أهداف الفعالية

            // الجدول الزمني
            $table->string('schedule_place')->nullable();
            $table->date('schedule_date')->nullable();
            $table->string('schedule_time')->nullable();

            // تقسيم المهام على الفريق
            $table->text('tasks_projects')->nullable(); // إدارة المشاريع
            $table->text('tasks_operations')->nullable(); // إدارة العمليات
            $table->text('tasks_mel')->nullable(); // المراقبة والتقييم والمسائلة والتعلم

            // جداول قابلة للتكرار (JSON)
            $table->json('content_items')->nullable(); // [{item, content, responsible, duration}]
            $table->json('logistics_items')->nullable(); // [{item, responsible}]
            $table->json('purchases_items')->nullable(); // [{item, responsible}]
            $table->json('media_items')->nullable(); // [{coverage, responsible}]
            $table->text('hr_notes')->nullable(); // الموارد البشرية
            $table->json('transport_items')->nullable(); // [{request, type, responsible}]
            $table->json('budget_items')->nullable(); // [{item, description, cost}]
            $table->decimal('budget_total', 14, 2)->nullable();

            $table->text('post_evaluation')->nullable(); // تقييم بعد الفعالية

            $table->string('status')->default('review'); // review|approved|finalized|rejected
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('referred_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finalized_at')->nullable();
            $table->text('reason')->nullable(); // سبب الرفض
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_cards');
    }
};
