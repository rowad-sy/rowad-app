<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('annex_documents', function (Blueprint $table) {
            $table->string('document_kind', 20)->default('structured')->after('status');
        });

        Schema::table('uploaded_documents', function (Blueprint $table) {
            $table->foreignId('annex_document_id')
                ->nullable()
                ->after('project_id')
                ->constrained('annex_documents')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('uploaded_documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('annex_document_id');
        });

        Schema::table('annex_documents', function (Blueprint $table) {
            $table->dropColumn('document_kind');
        });
    }
};
