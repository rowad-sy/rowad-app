<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_warnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->date('date');
            $table->text('reason');
            $table->enum('level', ['verbal', 'written', 'termination']);
            $table->boolean('is_folded')->default(false);
            $table->text('fold_reason')->nullable();
            $table->timestamp('folded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_warnings');
    }
};
