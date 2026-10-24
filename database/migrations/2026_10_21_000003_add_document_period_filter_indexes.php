<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('annex_documents', function (Blueprint $table) {
            $table->index('period_start', 'annex_docs_period_start_index');
        });

        Schema::table('uploaded_documents', function (Blueprint $table) {
            $table->index('document_date', 'uploaded_docs_document_date_index');
        });
    }

    public function down(): void
    {
        Schema::table('annex_documents', function (Blueprint $table) {
            $table->dropIndex('annex_docs_period_start_index');
        });

        Schema::table('uploaded_documents', function (Blueprint $table) {
            $table->dropIndex('uploaded_docs_document_date_index');
        });
    }
};
