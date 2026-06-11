<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    * جدول وسيط (Pivot) للمراكز والمشاريع
    * يحدد أي المشاريع فعالة في أي المراكز
    * علاقة كثير لكثير: مشروع واحد يمكن أن يكون في عدة مراكز، ومركز واحد يمكن أن يكون فيه عدة مشاريع
    */
    public function up(): void
    {
        Schema::create('center_project', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['center_id', 'project_id']); // منع التكرار
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('center_project');
    }
};
