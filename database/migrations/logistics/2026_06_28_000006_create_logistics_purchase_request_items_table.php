<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logistics_purchase_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained('logistics_purchase_requests')->cascadeOnDelete();
            $table->text('description');
            $table->integer('quantity');
            $table->string('unit');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('total_price', 12, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('logistics_purchase_requests', function (Blueprint $table) {
            $table->text('specifications')->nullable()->change();
            $table->integer('quantity')->nullable()->change();
            $table->string('unit')->nullable()->change();
            $table->decimal('expected_unit_price', 12, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logistics_purchase_request_items');

        Schema::table('logistics_purchase_requests', function (Blueprint $table) {
            $table->text('specifications')->nullable(false)->change();
            $table->integer('quantity')->nullable(false)->change();
            $table->string('unit')->nullable(false)->change();
            $table->decimal('expected_unit_price', 12, 2)->nullable(false)->change();
        });
    }
};
