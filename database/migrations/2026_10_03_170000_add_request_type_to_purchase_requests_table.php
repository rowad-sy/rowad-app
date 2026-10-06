<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
     * طلبات الصيانة تمر بنفس دورة طلبات الشراء بالكامل — نميّزها بعمود
     * request_type (purchase|maintenance) للتبويبات والفلاتر والتصدير.
     */
    public function up(): void
    {
        Schema::table('logistics_purchase_requests', function (Blueprint $table) {
            $table->string('request_type', 20)->default('purchase')->index()->after('request_number');
        });
    }

    public function down(): void
    {
        Schema::table('logistics_purchase_requests', function (Blueprint $table) {
            $table->dropIndex(['request_type']);
            $table->dropColumn('request_type');
        });
    }
};
