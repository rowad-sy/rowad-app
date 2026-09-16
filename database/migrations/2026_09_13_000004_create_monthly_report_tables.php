<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_report_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('title_ar');
            $table->string('slug')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->json('json_definition');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('monthly_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('monthly_report_templates')->cascadeOnDelete();
            $table->unsignedInteger('template_version')->default(1);
            $table->string('title')->nullable();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->string('period')->nullable();
            $table->string('status')->default('draft');
            $table->json('data')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('monthly_report_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('monthly_reports')->cascadeOnDelete();
            $table->string('block_key');
            $table->json('json_value')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('locked')->default(false);
            $table->timestamps();
            $table->unique(['report_id', 'block_key']);
        });

        Schema::create('monthly_report_signoffs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('monthly_reports')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('role_label')->nullable();
            $table->string('action');
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_report_signoffs');
        Schema::dropIfExists('monthly_report_blocks');
        Schema::dropIfExists('monthly_reports');
        Schema::dropIfExists('monthly_report_templates');
    }
};