<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movement_plans', function (Blueprint $table) {
            $table->foreignId('refer_to_pm2_id')->nullable()->constrained('users')->nullOnDelete()->after('refer_to_movement_officer_id');
        });
    }

    public function down(): void
    {
        Schema::table('movement_plans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('refer_to_pm2_id');
        });
    }
};