<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificate_designs', function (Blueprint $table) {
            $table->json('signatures_config')->nullable()->after('fields_config');
            $table->string('font_family', 100)->nullable()->after('year');
        });
    }

    public function down(): void
    {
        Schema::table('certificate_designs', function (Blueprint $table) {
            $table->dropColumn(['signatures_config', 'font_family']);
        });
    }
};
