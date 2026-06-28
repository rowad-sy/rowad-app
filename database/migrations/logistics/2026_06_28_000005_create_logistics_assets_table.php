<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logistics_assets', function (Blueprint $table) {
            $table->id();
            $table->string('asset_code')->unique();
            $table->string('name');
            $table->string('type');
            $table->foreignId('center_id')->constrained();
            $table->foreignId('project_id')->nullable()->constrained();
            $table->string('room_number')->nullable();
            $table->string('status');
            $table->text('notes')->nullable();
            $table->foreignId('recipient_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logistics_assets');
    }
};
