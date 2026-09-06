<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('level_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_level_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['academic_level_id', 'subject_id'], 'uq_level_subject');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('level_subjects');
    }
};
