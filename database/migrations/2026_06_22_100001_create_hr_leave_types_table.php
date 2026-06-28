<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->integer('annual_days')->default(0);
            $table->boolean('requires_approval')->default(true);
            $table->json('approver_ids')->nullable();
            $table->string('color')->default('#0d6efd');
            $table->string('icon')->default('bi-calendar');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_leave_types');
    }
};
