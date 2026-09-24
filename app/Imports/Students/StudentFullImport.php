<?php

namespace App\Imports\Students;

use App\Imports\Students\Sheets\StudentsSheetImport;
use App\Imports\Students\Sheets\EnrollmentsSheetImport;
use App\Imports\Students\Sheets\SignersSheetImport;
use App\Imports\Students\Sheets\SignatorySetsSheetImport;
use App\Imports\Students\Sheets\CertificatesSheetImport;
use App\Models\Admin\Student\CertificateSigner;
use App\Models\Admin\Student\CertificateSignatorySet;
use Maatwebsite\Excel\Concerns\SkipsUnknownSheets;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class StudentFullImport implements WithMultipleSheets, SkipsUnknownSheets
{
    /* student_code => student_id */
    public array $studentMap = [];

    /* signer code (or "name|role") => signer_id */
    public array $signerMap = [];

    /* set name => set_id */
    public array $setMap = [];

    public array $skippedSheets = [];

    public int $signersImported = 0;
    public int $setsImported = 0;
    public int $certificatesCreated = 0;
    public int $certificatesUpdated = 0;

    /*
     * المفاتيح النصية = أسماء الشيتات، لذا المطابقة تتم بالعنوان وليس بالترتيب.
     * الشيتات غير الموجودة في الملف تُتخطى تلقائياً (SkipsUnknownSheets).
     * الترتيب مهم: الموقعون ثم المجموعات قبل الشهادات (لتعبئة الخرائط).
     */
    public function sheets(): array
    {
        return [
            'students' => new StudentsSheetImport($this),
            'enrollments' => new EnrollmentsSheetImport($this),
            'signers' => new SignersSheetImport($this),
            'signatory_sets' => new SignatorySetsSheetImport($this),
            'certificates' => new CertificatesSheetImport($this),
        ];
    }

    public function onUnknownSheet($sheetName): void
    {
        $this->skippedSheets[] = (string) $sheetName;
    }

    public function registerSigner(string $key, int $id): void
    {
        $this->signerMap[$key] = $id;
    }

    /*
     * حلّ معرّف موقع من الرمز أو من (الاسم + الدور) مع البحث في قاعدة البيانات.
     */
    public function resolveSignerId(?string $code, ?string $name, string $role): ?int
    {
        $code = is_string($code) ? trim($code) : '';
        $name = is_string($name) ? trim($name) : '';

        if ($code !== '' && isset($this->signerMap[$code])) {
            return $this->signerMap[$code];
        }

        if ($code !== '') {
            $found = CertificateSigner::where('code', $code)->first();
            if ($found) {
                $this->registerSigner($code, $found->id);
                return $found->id;
            }
        }

        if ($name !== '') {
            $mapKey = $name . '|' . $role;
            if (isset($this->signerMap[$mapKey])) {
                return $this->signerMap[$mapKey];
            }
            $found = CertificateSigner::where('name_ar', $name)->where('role', $role)->first();
            if ($found) {
                $this->registerSigner($mapKey, $found->id);
                return $found->id;
            }
        }

        return null;
    }

    public function registerSet(string $name, int $id): void
    {
        $this->setMap[$name] = $id;
    }

    public function resolveSetId(?string $name): ?int
    {
        $name = is_string($name) ? trim($name) : '';
        if ($name === '') {
            return null;
        }

        if (isset($this->setMap[$name])) {
            return $this->setMap[$name];
        }

        $found = CertificateSignatorySet::where('name', $name)->first();
        if ($found) {
            $this->registerSet($name, $found->id);
            return $found->id;
        }

        return null;
    }
}
