<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificate_designs', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);
            $table->foreignId('course_id')->nullable()->constrained('courses')->nullOnDelete();
            $table->string('template_image')->nullable();
            $table->json('fields_config');
            $table->integer('year');
            $table->integer('start_number')->default(1);
            $table->integer('current_number')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_designs');
    }
};
