<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name_ar', 200);
            $table->string('name_en', 200)->nullable();
            $table->decimal('hours', 5, 2)->nullable();
            $table->decimal('weight', 5, 2)->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('course_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
