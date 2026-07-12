<?php

namespace App\Imports\Students;

use App\Imports\Students\Sheets\StudentsSheetImport;
use App\Imports\Students\Sheets\EnrollmentsSheetImport;
use App\Imports\Students\Sheets\CertificatesSheetImport;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class StudentFullImport implements WithMultipleSheets
{
    public array $studentMap = [];

    public function sheets(): array
    {
        return [
            new StudentsSheetImport($this),
            new EnrollmentsSheetImport($this),
            new CertificatesSheetImport($this),
        ];
    }
}
