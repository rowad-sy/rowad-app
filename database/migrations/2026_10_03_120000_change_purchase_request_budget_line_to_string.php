<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // خط الميزانية يصبح نصاً (مثال قياسي: 3.1.29)
        Schema::table('logistics_purchase_request_items', function (Blueprint $table) {
            $table->string('budget_line_temp', 60)->nullable()->after('budget_line');
        });

        // نقل القيم القديمة (نسخ PHP آمن عبر sqlite/mysql)
        DB::table('logistics_purchase_request_items')
            ->whereNotNull('budget_line')
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('logistics_purchase_request_items')
                        ->where('id', $row->id)
                        ->update(['budget_line_temp' => rtrim(rtrim(number_format((float) $row->budget_line, 2, '.', ''), '0'), '.')]);
                }
            });

        Schema::table('logistics_purchase_request_items', function (Blueprint $table) {
            $table->dropColumn('budget_line');
        });

        Schema::table('logistics_purchase_request_items', function (Blueprint $table) {
            $table->renameColumn('budget_line_temp', 'budget_line');
        });
    }

    public function down(): void
    {
        Schema::table('logistics_purchase_request_items', function (Blueprint $table) {
            $table->renameColumn('budget_line', 'budget_line_text');
        });

        Schema::table('logistics_purchase_request_items', function (Blueprint $table) {
            $table->decimal('budget_line', 12, 2)->nullable()->after('notes');
            $table->dropColumn('budget_line_text');
        });
    }
};
