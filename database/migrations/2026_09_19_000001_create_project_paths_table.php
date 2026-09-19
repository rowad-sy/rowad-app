<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
     * جدول المسارات
     * المسار يجمع عدداً من المشاريع (مشروع واحد ينتمي لمسار واحد)
     */
    public function up(): void
    {
        Schema::create('project_paths', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // اسم المسار
            $table->string('code')->nullable(); // كود المسار
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_paths');
    }
};
