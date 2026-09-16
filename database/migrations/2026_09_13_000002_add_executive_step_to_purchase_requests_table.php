<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logistics_purchase_requests', function (Blueprint $table) {
            $table->foreignId('refer_to_executive_id')->nullable()->constrained('users')->nullOnDelete()->after('refer_to_finance_id');
            $table->timestamp('finance_at')->nullable()->after('refer_to_executive_id');
        });
    }

    public function down(): void
    {
        Schema::table('logistics_purchase_requests', function (Blueprint $table) {
            $table->dropColumn('finance_at');
            $table->dropConstrainedForeignId('refer_to_executive_id');
        });
    }
};