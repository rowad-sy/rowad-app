<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_cards', function (Blueprint $table) {
            $table->text('tasks_grants')->nullable()->after('tasks_mel');
        });
    }

    public function down(): void
    {
        Schema::table('event_cards', function (Blueprint $table) {
            $table->dropColumn('tasks_grants');
        });
    }
};
