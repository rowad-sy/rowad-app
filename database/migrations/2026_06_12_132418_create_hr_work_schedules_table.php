<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_work_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->tinyInteger('day_of_week');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->boolean('is_day_off')->default(false);
            $table->timestamps();

            $table->unique(['employee_id', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_work_schedules');
    }
};
