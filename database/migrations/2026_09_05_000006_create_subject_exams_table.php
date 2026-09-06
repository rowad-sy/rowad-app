<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subject_exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->string('name_ar', 200);
            $table->string('type', 50)->nullable()->comment('pre/post/quiz/final/other — قبلي/بعدي/دوري/نهائي/أخرى');
            $table->decimal('max_score', 5, 2)->nullable()->comment('العلامة العليا للامتحان');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->index('subject_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_exams');
    }
};