<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movement_plan_entries', function (Blueprint $table) {
            $table->date('return_date')->nullable()->after('movement_date');
            $table->index(['movement_plan_id', 'return_date'], 'movement_entries_plan_return_date_index');
        });

        DB::table('movement_plan_entries')->whereNull('return_date')->update([
            'return_date' => DB::raw('movement_date'),
        ]);

        Schema::table('movement_plans', function (Blueprint $table) {
            $table->string('review_role_key', 80)->nullable()->after('refer_to_pm2_id');
            $table->timestamp('officer_accepted_at')->nullable()->after('refer_to_movement_officer_id');
        });
    }

    public function down(): void
    {
        Schema::table('movement_plans', function (Blueprint $table) {
            $table->dropColumn(['review_role_key', 'officer_accepted_at']);
        });

        Schema::table('movement_plan_entries', function (Blueprint $table) {
            $table->dropIndex('movement_entries_plan_return_date_index');
            $table->dropColumn('return_date');
        });
    }
};
