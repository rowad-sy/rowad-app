<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('purpose')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('needs_media_coverage')->default(false);
            $table->boolean('needs_costs')->default(false);
            $table->text('costs_details')->nullable();
            $table->boolean('needs_equipment')->default(false);
            $table->text('equipment_details')->nullable();
            $table->foreignId('assigned_to')->constrained('users');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('center_id')->nullable()->constrained('centers');
            $table->string('status')->default('pending');
            $table->boolean('executed')->nullable();
            $table->text('not_executed_reason')->nullable();
            $table->boolean('has_delay')->nullable();
            $table->text('delay_reason')->nullable();
            $table->boolean('media_coverage_done')->nullable();
            $table->text('no_media_coverage_reason')->nullable();
            $table->text('execution_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_tasks');
    }
};
