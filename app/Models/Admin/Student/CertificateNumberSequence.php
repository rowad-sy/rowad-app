<?php

namespace App\Models\Admin\Student;

use Illuminate\Database\Eloquent\Model;

class CertificateNumberSequence extends Model
{
    protected $table = 'certificate_number_sequence';

    protected $fillable = [
        'year', 'last_number',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'last_number' => 'integer',
        ];
    }

    public static function nextNumber(int $year): string
    {
        $sequence = static::where('year', $year)->lockForUpdate()->first();

        if (!$sequence) {
            $sequence = static::create([
                'year' => $year,
                'last_number' => 0,
            ]);
        }

        $sequence->increment('last_number');
        $num = $sequence->fresh()->last_number;

        return $year . '-' . str_pad($num, 5, '0', STR_PAD_LEFT);
    }
}
