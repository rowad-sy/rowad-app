<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    * جدول وسيط (Pivot) للمجموعات والمستخدمين
    * يربط المستخدمين بالمجموعات التي ينتمون إليها
    * مستخدم واحد يمكن أن ينتمي لعدة مجموعات
    * مجموعة واحدة يمكن أن تضم عدة مستخدمين
    */
    public function up(): void
    {
        Schema::create('group_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['group_id', 'user_id']); // منع التكرار
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_user');
    }
};
