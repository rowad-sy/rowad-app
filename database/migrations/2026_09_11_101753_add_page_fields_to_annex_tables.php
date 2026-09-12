<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('annex_documents', function (Blueprint $table) {
            $table->unsignedInteger('page_count')->default(1)->after('status');
        });

        Schema::table('annex_document_blocks', function (Blueprint $table) {
            $table->unsignedInteger('page_number')->default(1)->after('block_key');
        });
    }

    public function down(): void
    {
        Schema::table('annex_documents', function (Blueprint $table) {
            $table->dropColumn('page_count');
        });

        Schema::table('annex_document_blocks', function (Blueprint $table) {
            $table->dropColumn('page_number');
        });
    }
};
