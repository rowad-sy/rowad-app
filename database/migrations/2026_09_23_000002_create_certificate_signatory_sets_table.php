<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificate_signatory_sets', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);
            $table->foreignId('course_id')->nullable()->constrained('courses')->nullOnDelete();
            $table->foreignId('period_id')->nullable()->constrained('periods')->nullOnDelete();
            $table->foreignId('center_id')->nullable()->constrained('centers')->nullOnDelete();
            $table->foreignId('instructor_signer_id')->nullable()
                ->constrained('certificate_signers', 'id')->nullOnDelete();
            $table->foreignId('center_manager_signer_id')->nullable()
                ->constrained('certificate_signers', 'id')->nullOnDelete();
            $table->foreignId('project_manager_signer_id')->nullable()
                ->constrained('certificate_signers', 'id')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_signatory_sets');
    }
};
