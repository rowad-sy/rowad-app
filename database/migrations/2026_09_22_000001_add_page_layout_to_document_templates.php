<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('annex_templates', function (Blueprint $table) {
            $table->unsignedInteger('default_page_count')->default(1)->after('version');
        });

        Schema::table('monthly_report_templates', function (Blueprint $table) {
            $table->unsignedInteger('default_page_count')->default(1)->after('version');
        });

        Schema::table('monthly_reports', function (Blueprint $table) {
            $table->unsignedInteger('page_count')->default(1)->after('status');
        });

        Schema::table('monthly_report_blocks', function (Blueprint $table) {
            $table->unsignedInteger('page_number')->default(1)->after('block_key');
        });
    }

    public function down(): void
    {
        Schema::table('annex_templates', function (Blueprint $table) {
            $table->dropColumn('default_page_count');
        });

        Schema::table('monthly_report_templates', function (Blueprint $table) {
            $table->dropColumn('default_page_count');
        });

        Schema::table('monthly_reports', function (Blueprint $table) {
            $table->dropColumn('page_count');
        });

        Schema::table('monthly_report_blocks', function (Blueprint $table) {
            $table->dropColumn('page_number');
        });
    }
};
