<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name_ar', 200);
            $table->string('name_en', 200)->nullable();
            $table->string('code', 100)->nullable();
            $table->string('type', 50)->default('grade');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('project_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_levels');
    }
};
