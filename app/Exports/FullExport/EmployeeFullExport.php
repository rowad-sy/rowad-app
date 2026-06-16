<?php

namespace App\Exports\FullExport;

use App\Exports\FullExport\Sheets\ContactsSheet;
use App\Exports\FullExport\Sheets\ContractsSheet;
use App\Exports\FullExport\Sheets\EducationsSheet;
use App\Exports\FullExport\Sheets\EmployeesSheet;
use App\Exports\FullExport\Sheets\SalariesSheet;
use App\Exports\FullExport\Sheets\SchedulesSheet;
use App\Exports\FullExport\Sheets\WarningsSheet;
use App\Models\Admin\Hr\Employee;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class EmployeeFullExport implements WithMultipleSheets
{
    protected Collection $employees;

    public function __construct(?array $ids = null)
    {
        $query = Employee::with([
            'center', 'department', 'project', 'user',
            'educations', 'contacts', 'workSchedules',
            'contracts.jobPosition', 'salaries', 'warnings',
        ])->orderBy('employee_code');

        $this->employees = $ids ? $query->whereIn('id', $ids)->get() : $query->get();
    }

    public function sheets(): array
    {
        $employees = $this->employees;
        $codes = $employees->pluck('employee_code');

        return [
            new EmployeesSheet($employees),
            new EducationsSheet($employees->pluck('educations')->flatten()),
            new ContactsSheet($employees->pluck('contacts')->flatten()),
            new SchedulesSheet($employees->pluck('workSchedules')->flatten()),
            new ContractsSheet($employees->pluck('contracts')->flatten()),
            new SalariesSheet($employees->pluck('salaries')->flatten()),
            new WarningsSheet($employees->pluck('warnings')->flatten()),
        ];
    }
}
