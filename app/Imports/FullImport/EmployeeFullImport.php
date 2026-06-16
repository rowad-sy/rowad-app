<?php

namespace App\Imports\FullImport;

use App\Imports\FullImport\Sheets\ContactsSheetImport;
use App\Imports\FullImport\Sheets\ContractsSheetImport;
use App\Imports\FullImport\Sheets\EducationsSheetImport;
use App\Imports\FullImport\Sheets\EmployeesSheetImport;
use App\Imports\FullImport\Sheets\SalariesSheetImport;
use App\Imports\FullImport\Sheets\SchedulesSheetImport;
use App\Imports\FullImport\Sheets\WarningsSheetImport;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class EmployeeFullImport implements WithMultipleSheets
{
    public array $employeeMap = [];

    public function sheets(): array
    {
        return [
            new EmployeesSheetImport($this),
            new EducationsSheetImport($this),
            new ContactsSheetImport($this),
            new SchedulesSheetImport($this),
            new ContractsSheetImport($this),
            new SalariesSheetImport($this),
            new WarningsSheetImport($this),
        ];
    }
}
