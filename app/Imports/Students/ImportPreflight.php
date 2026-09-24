<?php

namespace App\Imports\Students;

use App\Models\Admin\Center;
use App\Models\Admin\Student\CertificateDesign;
use App\Models\Admin\Student\CertificateSignatorySet;
use App\Models\Admin\Student\CertificateSigner;
use App\Models\Admin\Student\Course;
use App\Models\Admin\Student\Period;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/*
 * فحص استباقي لملف الاستيراد الكامل: يقرأ أسماء الكيانات المرجعية
 * (مقررات، فترات، مراكز، تصاميم، مجموعات، موقعون) ويتأكد من وجودها
 * في قاعدة البيانات أو داخل الملف نفسه، ويُرجع رسائل خطأ واضحة
 * قبل بدء الاستيراد بدل الانكسار منتصفه بأخطاء SQL.
 */
class ImportPreflight
{
    public static function check(string $uploadedFilePath): array
    {
        $spreadsheet = IOFactory::load($uploadedFilePath);
        $errors = [];

        $enrollRows = self::rows($spreadsheet, 'enrollments');
        $setRows = self::rows($spreadsheet, 'signatory_sets');
        $certRows = self::rows($spreadsheet, 'certificates');
        $signerRows = self::rows($spreadsheet, 'signers');

        $names = ['course' => [], 'period' => [], 'center' => [], 'design' => [], 'set' => []];

        $gather = function (array $rows, array $columns) use (&$names) {
            foreach ($rows as $r) {
                foreach ($columns as $colIdx => $key) {
                    $v = trim((string) ($r[$colIdx] ?? ''));
                    if ($v !== '') {
                        $names[$key][$v] = true;
                    }
                }
            }
        };

        $gather($enrollRows, [1 => 'course', 2 => 'period']);
        $gather($setRows, [1 => 'course', 2 => 'period', 3 => 'center']);
        $gather($certRows, [2 => 'design', 3 => 'course', 4 => 'period', 5 => 'set']);

        $missingFrom = function (array $wanted, array $existing): array {
            return array_values(array_filter(array_keys($wanted), fn($n) => !isset($existing[$n])));
        };

        $index = fn($list) => array_fill_keys(
            array_map(fn($n) => trim((string) $n), $list), true
        );

        $missing = $missingFrom($names['course'], $index(Course::pluck('name_ar')->all()));
        if ($missing) {
            $errors[] = 'مقررات غير موجودة — أنشئها من صفحة المقررات بأسماء مطابقة تماماً: ' . implode(' ، ', $missing);
        }

        $missing = $missingFrom($names['period'], $index(Period::pluck('name_ar')->all()));
        if ($missing) {
            $errors[] = 'فترات غير موجودة — أنشئها من صفحة الفترات: ' . implode(' ، ', $missing);
        }

        $missing = $missingFrom($names['center'], $index(Center::pluck('name')->all()));
        if ($missing) {
            $errors[] = 'مراكز غير موجودة — أنشئها من صفحة المراكز: ' . implode(' ، ', $missing);
        }

        $missing = $missingFrom($names['design'], $index(CertificateDesign::pluck('name')->all()));
        if ($missing) {
            $errors[] = 'تصاميم شهادات غير موجودة — أنشئها من صفحة تصاميم الشهادات: ' . implode(' ، ', $missing);
        }

        $setsInDb = $index(CertificateSignatorySet::pluck('name')->all());
        $setsInFile = [];
        foreach ($setRows as $r) {
            $v = trim((string) ($r[0] ?? ''));
            if ($v !== '') {
                $setsInFile[$v] = true;
            }
        }
        $missing = array_values(array_filter(
            array_keys($names['set']),
            fn($n) => !isset($setsInDb[$n]) && !isset($setsInFile[$n])
        ));
        if ($missing) {
            $errors[] = 'مجموعات توقيعات مذكورة في الشهادات وغير معرّفة (لا في شيت signatory_sets ولا في النظام): ' . implode(' ، ', $missing);
        }

        $validRoles = array_keys(CertificateSigner::ROLES);
        $roleLabels = array_flip(CertificateSigner::ROLES);
        $bad = [];
        foreach ($signerRows as $r) {
            $role = trim((string) ($r[2] ?? ''));
            if ($role !== '' && !in_array($role, $validRoles, true) && !isset($roleLabels[$role])) {
                $bad[] = $role;
            }
        }
        if ($bad) {
            $errors[] = 'أدوار موقعين غير صالحة في شيت signers (المسموح: ' . implode('/', $validRoles)
                . ' أو تسمياتها العربية): ' . implode(' ، ', array_unique($bad));
        }

        return $errors;
    }

    protected static function rows(Spreadsheet $spreadsheet, string $title): array
    {
        if (!in_array($title, $spreadsheet->getSheetNames(), true)) {
            return [];
        }

        $rows = $spreadsheet->getSheetByName($title)->toArray(null, true, false, false);
        array_shift($rows);

        return array_values(array_filter($rows, fn($r) => trim((string) ($r[0] ?? '')) !== ''));
    }
}
