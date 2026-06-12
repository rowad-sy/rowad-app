<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_salaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->string('currency', 3)->default('SYP');
            $table->string('salary_unit', 50)->nullable();
            $table->decimal('base_salary', 12, 2)->default(0);
            $table->decimal('study_allowance', 10, 2)->default(0);
            $table->decimal('marriage_allowance', 10, 2)->default(0);
            $table->decimal('experience_allowance', 10, 2)->default(0);
            $table->decimal('transport_allowance', 10, 2)->default(0);
            $table->decimal('food_allowance', 10, 2)->default(0);
            $table->decimal('housing_allowance', 10, 2)->default(0);
            $table->decimal('mobile_allowance', 10, 2)->default(0);
            $table->decimal('risk_allowance', 10, 2)->default(0);
            $table->decimal('overtime_rate', 10, 2)->default(0);
            $table->decimal('deduction', 10, 2)->default(0);
            $table->decimal('total_salary', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_salaries');
    }
};
