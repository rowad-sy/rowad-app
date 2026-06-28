<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logistics_approval_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('min_amount', 12, 2)->default(0);
            $table->decimal('max_amount', 12, 2)->nullable();
            $table->integer('required_approvals')->default(1);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('logistics_approval_rule_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rule_id')->constrained('logistics_approval_rules')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logistics_approval_rule_user');
        Schema::dropIfExists('logistics_approval_rules');
    }
};
