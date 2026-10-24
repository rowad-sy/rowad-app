<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('annex_documents', function (Blueprint $table) {
            $table->date('period_start')->nullable()->after('period');
            $table->date('period_end')->nullable()->after('period_start');
            $table->index(['project_id', 'period_start'], 'annex_docs_project_period_start_index');
            $table->index(['template_id', 'period_start'], 'annex_docs_template_period_start_index');
            $table->index('period_end', 'annex_docs_period_end_index');
        });

        // Backfill only recognized month values. Keep the original free-text
        // period untouched so this migration can be rolled back safely.
        DB::table('annex_documents')
            ->whereNotNull('period')
            ->orderBy('id')
            ->chunkById(500, function ($documents) {
                foreach ($documents as $document) {
                    $period = trim((string) $document->period);
                    $months = preg_split('/\s+(?:-|–|—|to|إلى)\s+/iu', $period);

                    if (count($months) < 1 || count($months) > 2) {
                        continue;
                    }

                    $parsed = [];
                    foreach ($months as $month) {
                        $month = trim($month);
                        if (preg_match('/^(\d{4})[-\/]?(0[1-9]|1[0-2])$/', $month, $matches)) {
                            $parsed[] = sprintf('%04d-%02d', (int) $matches[1], (int) $matches[2]);
                            continue;
                        }

                        if (preg_match('/^(0[1-9]|1[0-2])[-\/](\d{4})$/', $month, $matches)) {
                            $parsed[] = sprintf('%04d-%02d', (int) $matches[2], (int) $matches[1]);
                            continue;
                        }

                        $parsed = [];
                        break;
                    }

                    if ($parsed === []) {
                        continue;
                    }

                    $start = new DateTimeImmutable($parsed[0] . '-01');
                    $endMonth = $parsed[1] ?? $parsed[0];
                    $end = new DateTimeImmutable($endMonth . '-01');
                    $end = $end->modify('last day of this month');

                    if ($end < $start) {
                        continue;
                    }

                    DB::table('annex_documents')->where('id', $document->id)->update([
                        'period_start' => $start->format('Y-m-d'),
                        'period_end' => $end->format('Y-m-d'),
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('annex_documents', function (Blueprint $table) {
            $table->dropIndex('annex_docs_project_period_start_index');
            $table->dropIndex('annex_docs_template_period_start_index');
            $table->dropIndex('annex_docs_period_end_index');
            $table->dropColumn(['period_start', 'period_end']);
        });
    }
};
