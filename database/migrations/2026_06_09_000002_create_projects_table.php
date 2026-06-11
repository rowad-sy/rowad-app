<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    * جدول المشاريع
    * هذا الجدول يخزن بيانات المشاريع التي تعمل عليها المؤسسة
    * كل مشروع له: اسم، وصف
    * المشروع يمكن أن يكون فعالاً في مركز واحد أو أكثر (علاقة متعدد لمتعدد)
    */
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // اسم المشروع (مثال: رواد العلم)
            $table->text('description')->nullable(); // وصف المشروع
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
