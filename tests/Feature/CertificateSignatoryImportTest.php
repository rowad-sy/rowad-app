<?php

use App\Exports\Students\StudentFullExport;
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
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

beforeEach(function () {
    $this->admin = User::factory()->create(['type' => 'super-admin']);
    $this->actingAs($this->admin);

    $this->center = Center::create(['name' => 'مركز الاختبار', 'address' => 'عنوان', 'phone' => '01']);
    $project = Project::create(['name' => 'مشروع الاختبار', 'description' => 'وصف']);
    $this->course = Course::create(['project_id' => $project->id, 'name_ar' => 'دورة الاختبار', 'name_en' => 'Test Course']);
    $this->period = Period::create([
        'project_id' => $project->id, 'name_ar' => 'فترة الاختبار',
        'year' => 2026, 'start_date' => '2026-01-01', 'end_date' => '2026-06-30', 'is_active' => true,
    ]);
    $this->student = Student::create([
        'student_code' => 'STU-TST-1', 'first_name_ar' => 'طالب', 'last_name_ar' => 'اختبار',
        'center_id' => $this->center->id, 'project_id' => $project->id,
        'gender' => 'male', 'status' => 'active', 'enrollment_date' => '2026-01-01',
    ]);

    $this->instructor = CertificateSigner::create(['code' => 'INS-1', 'name_ar' => 'مدرب الاختبار', 'role' => 'instructor']);
    $this->centerManager = CertificateSigner::create(['code' => 'CM-1', 'name_ar' => 'مدير المركز', 'role' => 'center_manager']);
    $this->projectManager = CertificateSigner::create(['code' => 'PM-1', 'name_ar' => 'مدير المشروع', 'role' => 'project_manager']);

    $this->set = CertificateSignatorySet::create([
        'name' => 'مجموعة تصدير واستيراد',
        'course_id' => $this->course->id,
        'period_id' => $this->period->id,
        'center_id' => $this->center->id,
        'instructor_signer_id' => $this->instructor->id,
        'center_manager_signer_id' => $this->centerManager->id,
        'project_manager_signer_id' => $this->projectManager->id,
    ]);

    $this->design = CertificateDesign::create([
        'name' => 'تصميم الاختبار',
        'year' => 2026,
        'fields_config' => [['id' => 1, 'type' => 'student_name', 'x_mm' => 10, 'y_mm' => 10, 'width_mm' => 50, 'height_mm' => 10, 'font_size' => 18, 'font_weight' => 700, 'color' => '#000000', 'align' => 'center']],
    ]);

    $this->cert = Certificate::create([
        'certificate_number' => '2026-90001',
        'design_id' => $this->design->id,
        'student_id' => $this->student->id,
        'signatory_set_id' => $this->set->id,
        'barcode_hash' => hash('sha256', 'seed-2026-90001' . config('app.key')),
        'issue_date' => '2026-05-01',
        'is_verified' => true,
    ]);
});

it('round-trips signers, sets and certificate links through full export then full import', function () {
    $relative = 'testing/cert-roundtrip.xlsx';
    Storage::disk('local')->makeDirectory('testing');

    Excel::store(new StudentFullExport([$this->student->id]), $relative, 'local');
    expect(Storage::disk('local')->exists($relative))->toBeTrue();

    // محاكاة نظام فارغ: نحذف المكتبة والمجموعة ونفصل الشهادة عنها
    $this->cert->update(['signatory_set_id' => null, 'is_verified' => false]);
    $this->set->delete();
    $this->instructor->delete();
    $this->centerManager->delete();
    $this->projectManager->delete();

    expect(CertificateSigner::count())->toBe(0)
        ->and(CertificateSignatorySet::count())->toBe(0);

    $import = new StudentFullImport();
    Excel::import($import, $relative, 'local');

    expect($import->signersImported)->toBe(3)
        ->and($import->setsImported)->toBe(1)
        ->and($import->certificatesUpdated)->toBe(1)
        ->and(CertificateSigner::where('code', 'INS-1')->value('name_ar'))->toBe('مدرب الاختبار')
        ->and(CertificateSigner::where('code', 'CM-1')->value('role'))->toBe('center_manager');

    $restoredSet = CertificateSignatorySet::where('name', 'مجموعة تصدير واستيراد')->first();
    expect($restoredSet)->not->toBeNull()
        ->and($restoredSet->course_id)->toBe($this->course->id)
        ->and($restoredSet->period_id)->toBe($this->period->id)
        ->and($restoredSet->center_id)->toBe($this->center->id)
        ->and($restoredSet->instructorSigner->name_ar)->toBe('مدرب الاختبار')
        ->and($restoredSet->centerManagerSigner->name_ar)->toBe('مدير المركز')
        ->and($restoredSet->projectManagerSigner->name_ar)->toBe('مدير المشروع');

    // الشهادة استعادت ربطها وحالة التوثيق (نعم => true)
    $this->cert->refresh();
    expect($this->cert->signatory_set_id)->toBe($restoredSet->id)
        ->and($this->cert->is_verified)->toBeTrue();

    Storage::disk('local')->delete($relative);
});

it('imports legacy 3-sheet files without signers or sets sheets', function () {
    $spreadsheet = new Spreadsheet();
    $spreadsheet->removeSheetByIndex(0);

    $students = $spreadsheet->createSheet();
    $students->setTitle('students');
    $students->fromArray([['student_code', 'first_name_ar', 'last_name_ar']], null, 'A1');
    $students->fromArray([['STU-LEGACY-1', 'طالب', 'قديم']], null, 'A2');

    $enrollments = $spreadsheet->createSheet();
    $enrollments->setTitle('enrollments');
    $enrollments->fromArray([['student_code', 'course_name_ar', 'period_name_ar']], null, 'A1');

    $certs = $spreadsheet->createSheet();
    $certs->setTitle('certificates');
    $certs->fromArray([['student_code', 'certificate_number', 'design_name', 'issue_date', 'is_verified']], null, 'A1');
    $certs->fromArray([['STU-LEGACY-1', '2026-90002', 'تصميم الاختبار', '2026-03-03', 'نعم']], null, 'A2');

    $relative = 'testing/cert-legacy.xlsx';
    Storage::disk('local')->makeDirectory('testing');
    $writer = new Xlsx($spreadsheet);
    $writer->save(Storage::disk('local')->path($relative));

    $before = Certificate::count();
    $import = new StudentFullImport();
    Excel::import($import, $relative, 'local');

    expect(Student::where('student_code', 'STU-LEGACY-1')->exists())->toBeTrue()
        ->and(Certificate::count())->toBe($before + 1)
        ->and($import->certificatesCreated)->toBe(1)
        ->and(in_array('signers', $import->skippedSheets, true))->toBeTrue()
        ->and(in_array('signatory_sets', $import->skippedSheets, true))->toBeTrue();

    $cert = Certificate::where('certificate_number', '2026-90002')->first();
    expect($cert->barcode_hash)->not->toBeNull()
        ->and($cert->is_verified)->toBeTrue()
        ->and($cert->signatory_set_id)->toBeNull();

    Storage::disk('local')->delete($relative);
});

it('auto-resolves signatory set by course, period and student center when no set name given', function () {    $relative = 'testing/cert-auto.xlsx';
    Storage::disk('local')->makeDirectory('testing');

    $spreadsheet = new Spreadsheet();
    $spreadsheet->removeSheetByIndex(0);

    $students = $spreadsheet->createSheet();
    $students->setTitle('students');
    $students->fromArray([['student_code', 'first_name_ar', 'last_name_ar', 'center_name']], null, 'A1');
    $students->fromArray([['STU-AUTO-1', 'طالب', 'تلقائي', 'مركز الاختبار']], null, 'A2');

    $certs = $spreadsheet->createSheet();
    $certs->setTitle('certificates');
    $certs->fromArray([['student_code', 'certificate_number', 'design_name', 'course_name_ar', 'period_name_ar', 'signatory_set_name', 'issue_date']], null, 'A1');
    $certs->fromArray([['STU-AUTO-1', '2026-90003', 'تصميم الاختبار', 'دورة الاختبار', 'فترة الاختبار', '', '2026-04-04']], null, 'A2');

    $writer = new Xlsx($spreadsheet);
    $writer->save(Storage::disk('local')->path($relative));

    $import = new StudentFullImport();
    Excel::import($import, $relative, 'local');

    $cert = Certificate::where('certificate_number', '2026-90003')->first();
    expect($cert)->not->toBeNull()
        ->and($cert->signatory_set_id)->toBe($this->set->id);

    Storage::disk('local')->delete($relative);
});

it('renders center_name field in certificate preview from the student center', function () {
    $certDesign = CertificateDesign::create([
        'name' => 'تصميم بمركز',
        'year' => 2026,
        'fields_config' => [
            ['id' => 1, 'type' => 'student_name', 'x_mm' => 10, 'y_mm' => 10, 'width_mm' => 60, 'height_mm' => 10, 'font_size' => 18, 'font_weight' => 700, 'color' => '#000', 'align' => 'center'],
            ['id' => 2, 'type' => 'center_name', 'x_mm' => 10, 'y_mm' => 30, 'width_mm' => 60, 'height_mm' => 10, 'font_size' => 14, 'font_weight' => 500, 'color' => '#000', 'align' => 'center'],
        ],
    ]);
    $cert = Certificate::create([
        'certificate_number' => '2026-90005',
        'design_id' => $certDesign->id,
        'student_id' => $this->student->id,
        'barcode_hash' => hash('sha256', 'seed-2026-90005' . config('app.key')),
        'issue_date' => '2026-05-01',
    ]);

    $response = $this->get(route('admin.students.certificates.preview', $cert));
    $response->assertOk();
    $response->assertSee('مركز الاختبار', false);
});

it('preflight reports missing courses, centers, designs and unresolvable sets', function () {
    $spreadsheet = new Spreadsheet();
    $spreadsheet->removeSheetByIndex(0);

    $enr = $spreadsheet->createSheet();
    $enr->setTitle('enrollments');
    $enr->fromArray([['student_code', 'course_name_ar', 'period_name_ar']], null, 'A1');
    $enr->fromArray([['STU-X', 'مقرر غير موجود', 'فترة غير موجودة']], null, 'A2');

    $sets = $spreadsheet->createSheet();
    $sets->setTitle('signatory_sets');
    $sets->fromArray([['name', 'course_name_ar', 'period_name_ar', 'center_name']], null, 'A1');
    $sets->fromArray([['set-س', 'مقرر غير موجود', 'فترة غير موجودة', 'مركز غير موجود']], null, 'A2');

    $certs = $spreadsheet->createSheet();
    $certs->setTitle('certificates');
    $certs->fromArray([['student_code', 'certificate_number', 'design_name', 'course_name_ar', 'period_name_ar', 'signatory_set_name']], null, 'A1');
    $certs->fromArray([['STU-X', '', 'تصميم مفقود', 'مقرر غير موجود', 'فترة غير موجودة', 'set-مجهولة']], null, 'A2');

    $relative = 'testing/preflight.xlsx';
    Storage::disk('local')->makeDirectory('testing');
    (new Xlsx($spreadsheet))->save(Storage::disk('local')->path($relative));

    $errors = \App\Imports\Students\ImportPreflight::check(Storage::disk('local')->path($relative));
    Storage::disk('local')->delete($relative);

    $text = implode(' | ', $errors);
    expect($errors)->not->toBeEmpty()
        ->and($text)->toContain('مقرر غير موجود')
        ->and($text)->toContain('فترة غير موجودة')
        ->and($text)->toContain('مركز غير موجود')
        ->and($text)->toContain('تصميم مفقود')
        ->and($text)->toContain('set-مجهولة');
});
