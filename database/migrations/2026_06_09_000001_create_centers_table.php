<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    * جدول المراكز
    * هذا الجدول يخزن بيانات المراكز التابعة للمؤسسة
    * كل مركز له: اسم، عنوان، هاتف رسمي
    */
    public function up(): void
    {
        Schema::create('centers', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // اسم المركز (مثال: مركز عفرين)
            $table->string('address')->nullable(); // عنوان المركز
            $table->string('phone')->nullable(); // هاتف المركز الرسمي
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('centers');
    }
};
