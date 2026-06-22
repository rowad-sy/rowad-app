<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tech_equipment', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->nullable();
            $table->string('serial_number')->nullable();
            $table->enum('condition', ['a', 'b', 'c', 'd', 'e'])->default('c');
            $table->string('room')->nullable();
            $table->foreignId('center_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('type');
            $table->index('condition');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tech_equipment');
    }
};
