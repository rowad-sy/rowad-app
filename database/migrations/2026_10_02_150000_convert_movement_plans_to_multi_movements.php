<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movement_plan_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movement_plan_id')->constrained('movement_plans')->cascadeOnDelete();
            $table->date('movement_date');
            $table->time('departure_time')->nullable();
            $table->time('return_time')->nullable();
            $table->string('from_location')->nullable();
            $table->string('to_location')->nullable();
            $table->text('purpose');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['movement_plan_id', 'movement_date']);
        });

        Schema::table('movement_plans', function (Blueprint $table) {
            $table->date('plan_month')->nullable()->after('project_id');
        });

        foreach (DB::table('movement_plans')->get() as $plan) {
            $month = $plan->movement_date ? date('Y-m-01', strtotime($plan->movement_date)) : date('Y-m-01');

            DB::table('movement_plan_entries')->insert([
                'movement_plan_id' => $plan->id,
                'movement_date' => $plan->movement_date ?: $month,
                'departure_time' => $plan->departure_time,
                'return_time' => $plan->return_time,
                'from_location' => $plan->from_location,
                'to_location' => $plan->to_location,
                'purpose' => $plan->purpose,
                'notes' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('movement_plans')->where('id', $plan->id)->update(['plan_month' => $month]);
        }

        Schema::table('movement_plans', function (Blueprint $table) {
            $table->dropColumn(['movement_date', 'departure_time', 'return_time', 'from_location', 'to_location', 'purpose']);
        });
    }

    public function down(): void
    {
        Schema::table('movement_plans', function (Blueprint $table) {
            $table->date('movement_date')->nullable()->after('project_id');
            $table->time('departure_time')->nullable();
            $table->time('return_time')->nullable();
            $table->string('from_location')->nullable();
            $table->string('to_location')->nullable();
            $table->text('purpose')->nullable();
        });

        foreach (DB::table('movement_plan_entries')->orderBy('movement_plan_id')->orderBy('movement_date')->get()->groupBy('movement_plan_id') as $planId => $entries) {
            $first = $entries->first();
            DB::table('movement_plans')->where('id', $planId)->update([
                'movement_date' => $first->movement_date,
                'departure_time' => $first->departure_time,
                'return_time' => $first->return_time,
                'from_location' => $first->from_location,
                'to_location' => $first->to_location,
                'purpose' => $first->purpose,
            ]);
        }

        Schema::dropIfExists('movement_plan_entries');

        Schema::table('movement_plans', function (Blueprint $table) {
            $table->dropColumn('plan_month');
        });
    }
};
