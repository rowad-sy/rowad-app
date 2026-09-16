<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media_plans', function (Blueprint $table) {
            $table->string('status')->default('review')->after('note');
            $table->foreignId('refer_to_direct_manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('refer_to_pm2_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('refer_to_media_manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('refer_to_media_officer_id')->nullable()->constrained('users')->nullOnDelete()->after('refer_to_media_manager_id');
            $table->timestamp('locked_at')->nullable()->after('refer_to_media_officer_id');
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete()->after('locked_at');
            $table->timestamp('approved_at')->nullable()->after('locked_by');
            $table->string('reason')->nullable()->after('approved_at');
        });

        Schema::table('media_plan_events', function (Blueprint $table) {
            $table->string('execution_status')->nullable()->after('notes');
            $table->text('execution_note')->nullable()->after('execution_status');
            $table->foreignId('execution_by')->nullable()->constrained('users')->nullOnDelete()->after('execution_note');
            $table->timestamp('execution_at')->nullable()->after('execution_by');
        });
    }

    public function down(): void
    {
        Schema::table('media_plan_events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('execution_by');
            $table->dropColumn(['execution_status', 'execution_note', 'execution_at']);
        });

        Schema::table('media_plans', function (Blueprint $table) {
            $table->dropColumn(['status', 'locked_at', 'approved_at', 'reason']);
            $table->dropConstrainedForeignId('refer_to_media_officer_id');
            $table->dropConstrainedForeignId('refer_to_media_manager_id');
            $table->dropConstrainedForeignId('refer_to_pm2_id');
            $table->dropConstrainedForeignId('refer_to_direct_manager_id');
            $table->dropConstrainedForeignId('locked_by');
        });
    }
};