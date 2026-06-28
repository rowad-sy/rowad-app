<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logistics_warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('center_id')->constrained();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('logistics_warehouse_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('logistics_warehouses')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('quantity')->default(0);
            $table->string('unit');
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('logistics_deleted_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->nullable()->constrained('logistics_warehouses')->nullOnDelete();
            $table->string('item_name');
            $table->text('description')->nullable();
            $table->integer('quantity');
            $table->string('unit');
            $table->string('delete_reason');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logistics_deleted_items');
        Schema::dropIfExists('logistics_warehouse_items');
        Schema::dropIfExists('logistics_warehouses');
    }
};
