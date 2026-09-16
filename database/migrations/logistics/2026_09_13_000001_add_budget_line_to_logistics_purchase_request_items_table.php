<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logistics_purchase_request_items', function (Blueprint $table) {
            $table->decimal('budget_line', 12, 2)->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('logistics_purchase_request_items', function (Blueprint $table) {
            $table->dropColumn('budget_line');
        });
    }
};