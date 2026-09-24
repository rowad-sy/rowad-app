<?php

use App\Imports\Students\ImportPreflight;
use App\Imports\Students\StudentFullImport;
use App\Models\Admin\Center;
use App\Models\Admin\Project;
use App\Models\Admin\Student\Certificate;
use App\Models\Admin\Student\CertificateDesign;
use App\Models\Admin\Student\CertificateSignatorySet;
use App\Models\Admin\Student\CertificateSigner;
use App\Models\Admin\Student\Course;
use App\Models\Admin\Student\Period;
use App\Models\Admin\Student\Student;
use App\Models\Admin\Student\StudentEnrollment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

const REAL_FILE = 'C:/xampp/htdocs/rowad-app/project-files/stage 1/شهادات/تقاني/2026/الدورة الثالثة/الطلاب/استيراد - شهادات الدورة الثالثة 2026.xlsx';

beforeEach(function () {
    User::factory()->create(['type' => 'super-admin']);

    $project = Project::create(['name' => 'معهد الرواد للعلوم التقنية', 'description' => '']);
    foreach (['جرابلس', 'الأتارب', 'الباب', 'مارع', 'عفرين', 'سلقين'] as $c) {
        Center::create(['name' => $c, 'address' => '', 'phone' => '']);
    }
    foreach ([
        'قيادة الحاسوب - المستوى الأول', 'قيادة الحاسوب - المستوى الثاني', 'الذكاء الصناعي التوليدي',
        'برمجة ويب HTML\CSS', 'تصميم جرافيكي - PHOTOSHOP', 'أساسيات البرمجة CS50', 'البرمجة بلغة الـ PYTHON',
    ] as $course) {
        Course::create(['project_id' => $project->id, 'name_ar' => $course]);
    }
    Period::create([
        'project_id' => $project->id, 'name_ar' => 'الثالثة', 'year' => 2026,
        'start_date' => '2026-09-20', 'end_date' => '2026-12-31', 'is_active' => true,
    ]);
    CertificateDesign::create([
        'name' => 'تصميم الدورة الثالثة 2026', 'year' => 2026,
        'fields_config' => [['id' => 1, 'type' => 'student_name', 'x_mm' => 88.5, 'y_mm' => 62, 'width_mm' => 120, 'height_mm' => 14, 'font_size' => 22, 'font_weight' => 700, 'color' => '#1a1a1a', 'align' => 'center']],
    ]);
});

it('passes preflight on the real third-cycle import file', function () {
    expect(ImportPreflight::check(REAL_FILE))->toBe([]);
})->skip(!is_file(REAL_FILE), 'real dataset not present in this environment');

it('imports the entire real file end to end with full linkage', function () {
    $import = new StudentFullImport();
    Excel::import($import, REAL_FILE);

    expect(Student::count())->toBe(575)
        ->and(StudentEnrollment::count())->toBe(575)
        ->and(CertificateSigner::count())->toBe(17)
        ->and(CertificateSignatorySet::count())->toBe(36)
        ->and(Certificate::count())->toBe(572);

    // كل التسجيلات تحمل تاريخ التحاق صحيح
    expect((int) StudentEnrollment::whereDate('enrollment_date', '2026-09-20')->count())->toBe(575);

    // كل الشهادات مربوطة بتصميم ومجموعة وتوقيعها فريد وأرقامها متسلسلة بلا تكرار
    expect(Certificate::whereNull('design_id')->count())->toBe(0)
        ->and(Certificate::whereNull('signatory_set_id')->count())->toBe(0)
        ->and(Certificate::whereNull('barcode_hash')->count())->toBe(0)
        ->and(Certificate::distinct('certificate_number')->count('certificate_number'))->toBe(572);

    // التوزيع: كل مجموعة يوقعها مدرب موجود، وعدد الشهادات لكل مجموعة = طلبتها لناقص الراسبين
    $setsWithTrainer = CertificateSignatorySet::whereNotNull('instructor_signer_id')->count();
    expect($setsWithTrainer)->toBe(36);

    // شهادة واحدة لمؤيد فضلي (STU-00646) مرتبطة بمجموعته الصحيحة
    $student = Student::where('student_code', 'STU-00646')->first();
    $cert = Certificate::where('student_id', $student->id)->first();
    expect($cert)->not->toBeNull()
        ->and($cert->signatorySet->instructorSigner->name_ar)->toBe('عبد الرحمن عثمان')
        ->and($cert->signatorySet->center->name)->toBe('جرابلس')
        ->and($cert->signatorySet->course->name_ar)->toBe('قيادة الحاسوب - المستوى الأول');

    // إحصائيات المستورد
    expect($import->signersImported)->toBe(17)
        ->and($import->setsImported)->toBe(36)
        ->and($import->certificatesCreated)->toBe(572)
        ->and($import->certificatesUpdated)->toBe(0);
})->skip(!is_file(REAL_FILE), 'real dataset not present in this environment');
