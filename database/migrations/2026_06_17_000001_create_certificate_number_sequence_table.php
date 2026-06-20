<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificate_number_sequence', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            $table->bigInteger('last_number')->default(0);
            $table->timestamps();

            $table->unique('year');
        });

        // Migrate existing per-design current_number into the global sequence
        $maxCertificate = DB::table('certificates')
            ->selectRaw('SUBSTRING(certificate_number, 1, 4) as year, MAX(CAST(SUBSTRING(certificate_number, 6, 10) AS UNSIGNED)) as max_num')
            ->groupBy('year')
            ->orderBy('year')
            ->get();

        if ($maxCertificate->isNotEmpty()) {
            foreach ($maxCertificate as $row) {
                DB::table('certificate_number_sequence')->insert([
                    'year' => $row->year,
                    'last_number' => $row->max_num,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } else {
            DB::table('certificate_number_sequence')->insert([
                'year' => date('Y'),
                'last_number' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_number_sequence');
    }
};
