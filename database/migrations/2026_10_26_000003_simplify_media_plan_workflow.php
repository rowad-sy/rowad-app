<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media_plans', function (Blueprint $table) {
            // Historical plans retain the old multi-stage workflow.
            $table->string('workflow_version', 20)->default('legacy')->after('status');
        });

        Schema::table('media_plan_events', function (Blueprint $table) {
            // Existing entries have an unknown duration; keep them as legacy point-in-time events.
            $table->time('event_end_time')->nullable()->after('event_time');
        });
    }

    public function down(): void
    {
        Schema::table('media_plan_events', function (Blueprint $table) {
            $table->dropColumn('event_end_time');
        });

        Schema::table('media_plans', function (Blueprint $table) {
            $table->dropColumn('workflow_version');
        });
    }
};
