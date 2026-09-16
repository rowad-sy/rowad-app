<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('physio_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('physio_patients')->cascadeOnDelete();
            $table->date('session_date');
            $table->unsignedInteger('session_number')->default(1);
            $table->text('what_done');
            $table->foreignId('therapist_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('physio_sessions');
    }
};