<?php

namespace App\Exports\Students;

use App\Exports\Students\Sheets\StudentsSheet;
use App\Exports\Students\Sheets\EnrollmentsSheet;
use App\Exports\Students\Sheets\CertificatesSheet;
use App\Exports\Students\Sheets\SignersSheet;
use App\Exports\Students\Sheets\SignatorySetsSheet;
use App\Models\Admin\Student\CertificateSignatorySet;
use App\Models\Admin\Student\CertificateSigner;
use App\Models\Admin\Student\Student;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class StudentFullExport implements WithMultipleSheets
{
    protected Collection $students;

    public function __construct(?array $ids = null)
    {
        $query = Student::with([
            'center', 'projects', 'enrollments.course', 'enrollments.period',
            'certificates.design', 'certificates.enrollment.course', 'certificates.enrollment.period',
            'certificates.signatorySet.instructorSigner', 'certificates.signatorySet.centerManagerSigner',
            'certificates.signatorySet.projectManagerSigner',
        ])->orderBy('student_code');

        $this->students = $ids ? $query->whereIn('id', $ids)->get() : $query->get();
    }

    public function sheets(): array
    {
        $students = $this->students;

        return [
            new StudentsSheet($students),
            new EnrollmentsSheet($students->pluck('enrollments')->flatten()),
            new SignersSheet(CertificateSigner::orderBy('role')->orderBy('name_ar')->get()),
            new SignatorySetsSheet(
                CertificateSignatorySet::with(['course', 'period', 'center', 'instructorSigner', 'centerManagerSigner', 'projectManagerSigner'])
                    ->orderBy('name')
                    ->get()
            ),
            new CertificatesSheet($students->pluck('certificates')->flatten()),
        ];
    }
}
