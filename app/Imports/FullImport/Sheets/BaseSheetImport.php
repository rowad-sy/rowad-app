<?php

namespace App\Imports\FullImport\Sheets;

use App\Imports\FullImport\EmployeeFullImport;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithTitle;

abstract class BaseSheetImport implements ToCollection, WithHeadingRow, WithTitle
{
    public function __construct(protected EmployeeFullImport $parent) {}

    public function headingRow(): int
    {
        return 1;
    }
}
