<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logistics_purchase_requests', function (Blueprint $table) {
            $table->string('budget_number')->nullable()->after('signature_path');
            $table->timestamp('locked_at')->nullable()->after('budget_number');
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete()->after('locked_at');
            $table->foreignId('refer_to_logistics_id')->nullable()->constrained('users')->nullOnDelete()->after('locked_by');
            $table->foreignId('refer_to_direct_manager_id')->nullable()->constrained('users')->nullOnDelete()->after('refer_to_logistics_id');
            $table->foreignId('refer_to_pm2_id')->nullable()->constrained('users')->nullOnDelete()->after('refer_to_direct_manager_id');
            $table->foreignId('refer_to_finance_id')->nullable()->constrained('users')->nullOnDelete()->after('refer_to_pm2_id');
            $table->timestamp('approved_at')->nullable()->after('refer_to_finance_id');
        });
    }

    public function down(): void
    {
        Schema::table('logistics_purchase_requests', function (Blueprint $table) {
            $table->dropColumn(['approved_at', 'budget_number', 'locked_at']);
            $table->dropConstrainedForeignId('refer_to_finance_id');
            $table->dropConstrainedForeignId('refer_to_pm2_id');
            $table->dropConstrainedForeignId('refer_to_direct_manager_id');
            $table->dropConstrainedForeignId('refer_to_logistics_id');
            $table->dropConstrainedForeignId('locked_by');
        });
    }
};