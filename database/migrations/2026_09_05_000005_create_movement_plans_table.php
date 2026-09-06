<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movement_plans', function (Blueprint $table) {
            $table->id();
            $table->string('request_number')->unique();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('center_id')->nullable()->constrained('centers')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->date('movement_date');
            $table->time('departure_time')->nullable();
            $table->time('return_time')->nullable();
            $table->string('from_location')->nullable();
            $table->string('to_location')->nullable();
            $table->text('purpose');
            $table->text('notes')->nullable();
            $table->foreignId('refer_to_movement_officer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('status')->default('review');
            $table->string('reason')->nullable();
            $table->timestamps();
        });

        Schema::create('movement_plan_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movement_plan_id')->constrained('movement_plans')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role_label')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movement_plan_recipients');
        Schema::dropIfExists('movement_plans');
    }
};