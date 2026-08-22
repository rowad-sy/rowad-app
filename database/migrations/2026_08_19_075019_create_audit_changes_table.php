<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_changes', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('audit_log_id');
            $table->string('field', 191);
            $table->string('label', 191)->nullable();
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->string('value_type', 20)->default('string');
            $table->boolean('is_masked')->default(false);

            $table->timestamps();

            $table->foreign('audit_log_id')
                ->references('id')
                ->on('audit_logs')
                ->onDelete('cascade');

            $table->index('field');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_changes');
    }
};
