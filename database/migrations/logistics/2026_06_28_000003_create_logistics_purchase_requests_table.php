<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logistics_purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number')->unique();
            $table->foreignId('user_id')->constrained();
            $table->text('specifications');
            $table->integer('quantity');
            $table->string('unit');
            $table->decimal('expected_unit_price', 12, 2);
            $table->decimal('expected_total_price', 12, 2);
            $table->foreignId('center_id')->nullable()->constrained();
            $table->foreignId('project_id')->nullable()->constrained();
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->string('signature_path')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('logistics_purchase_request_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained('logistics_purchase_requests')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logistics_purchase_request_approvals');
        Schema::dropIfExists('logistics_purchase_requests');
    }
};
