<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            $table->char('request_id', 36)->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_type', 20)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();

            $table->string('model', 191);
            $table->string('model_name', 191);
            $table->unsignedBigInteger('model_id');
            $table->string('event', 20);

            $table->text('description')->nullable();

            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            $table->char('hash', 64);
            $table->char('prev_hash', 64)->nullable();

            $table->timestamp('created_at');

            $table->index(['model', 'model_id']);
            $table->index('model_name');
            $table->index('user_id');
            $table->index('event');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
