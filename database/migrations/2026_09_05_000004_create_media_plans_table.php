<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_plans', function (Blueprint $table) {
            $table->id();
            $table->date('month_date');
            $table->foreignId('center_id')->nullable()->constrained('centers')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('media_plan_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_plan_id')->constrained('media_plans')->cascadeOnDelete();
            $table->date('event_date');
            $table->string('office')->nullable();
            $table->string('event_name');
            $table->string('day')->nullable();
            $table->time('event_time');
            $table->string('location')->nullable();
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('summary')->nullable();
            $table->string('coverage_type')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['media_plan_id', 'event_date', 'event_time'], 'media_plan_events_no_conflict');
        });

        Schema::create('media_plan_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_plan_event_id')->constrained('media_plan_events')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('comment');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_plan_comments');
        Schema::dropIfExists('media_plan_events');
        Schema::dropIfExists('media_plans');
    }
};