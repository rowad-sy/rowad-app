<?php

namespace App\Http\Controllers\Admin\Student;

use App\Http\Controllers\Controller;
use App\Models\Admin\Student\Certificate;
use App\Models\Admin\Student\CertificateDesign;
use App\Models\Admin\Student\CertificateSignatorySet;
use App\Models\Admin\Student\Course;
use App\Models\Admin\Student\Period;
use App\Models\Admin\Student\Student;
use App\Models\Admin\Student\CertificateNumberSequence;
use App\Models\Admin\Student\StudentEnrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CertificateController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\Student\Certificate,view')->only(['index', 'designs', 'show', 'preview', 'printBatch']);
        $this->middleware('permission:App\Models\Admin\Student\Certificate,create')->only(['createDesign', 'storeDesign', 'editDesign', 'updateDesign', 'issue', 'generateCertificates']);
        $this->middleware('permission:App\Models\Admin\Student\Certificate,delete')->only(['destroyDesign']);
    }

    // ─── Issued Certificates List ───

    public function index(Request $request)
    {
        $search = $request->input('search');
        $perPage = (int) $request->input('per_page', 10);
        $designId = $request->input('design_id');

        $showCancelled = $request->input('show_cancelled');
        $certificates = Certificate::with(['student', 'design'])
            ->when(!$showCancelled, fn($q) => $q->whereNull('cancelled_at'))
            ->when($search, fn($q, $v) => $q->whereHas('student', fn($sq) => $sq->where('first_name_ar', 'like', "%{$v}%")->orWhere('student_code', 'like', "%{$v}%"))
                ->orWhere('certificate_number', 'like', "%{$v}%"))
            ->when($designId, fn($q, $v) => $q->where('design_id', $v))
            ->orderBy('id', 'desc')
            ->paginate($perPage)
            ->appends($request->only(['search', 'per_page', 'design_id', 'show_cancelled']));

        $designs = CertificateDesign::orderBy('name')->get();

        return view('admin.students.certificates.index', compact('certificates', 'search', 'perPage', 'designId', 'designs'));
    }

    // ─── Designs List ───

    public function designs(Request $request)
    {
        $search = $request->input('search');
        $courseId = $request->input('course_id');
        $year = $request->input('year');
        $perPage = (int) $request->input('per_page', 12);

        $designs = CertificateDesign::with('course')->withCount('certificates')
            ->when($search, fn($q, $v) => $q->where('name', 'like', "%{$v}%"))
            ->when($courseId, fn($q, $v) => $q->where('course_id', $v))
            ->when($year, fn($q, $v) => $q->where('year', $v))
            ->orderBy('id', 'desc')
            ->paginate($perPage)
            ->appends($request->only(['search', 'course_id', 'year', 'per_page']));

        $courses = Course::orderBy('name_ar')->get();
        $years = CertificateDesign::select('year')->distinct()->orderBy('year', 'desc')->pluck('year');
        $view = $request->input('view', 'table');

        return view('admin.students.certificates.designs', compact('designs', 'courses', 'years', 'search', 'courseId', 'year', 'view'));
    }

    // ─── Designer (Create) ───

    public function createDesign(Request $request)
    {
        $courses = Course::orderBy('name_ar')->get();
        $studentIds = $request->input('student_ids', []);
        $courseId = $request->input('course_id');
        $periodId = $request->input('period_id');
        $students = Student::whereIn('id', $studentIds)->get();

        return view('admin.students.certificates.designer', compact(
            'courses', 'studentIds', 'courseId', 'periodId', 'students'
        ));
    }

    // ─── Designer (Edit) ───

    public function editDesign($id)
    {
        $design = CertificateDesign::findOrFail($id);
        $courses = Course::orderBy('name_ar')->get();

        return view('admin.students.certificates.designer', compact('design', 'courses'));
    }

    // ─── Save Design ───

    public function storeDesign(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'course_id' => 'nullable|exists:courses,id',
            'template_image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'fields_config' => 'required|json',
            'signatures_config' => 'nullable|json',
            'font_family' => 'nullable|string|max:100',
            'year' => 'required|integer|min:2000|max:2100',
        ]);

        $fieldsConfig = json_decode($validated['fields_config'], true);
        $signaturesConfig = !empty($validated['signatures_config']) ? json_decode($validated['signatures_config'], true) : null;

        $data = [
            'name' => $validated['name'],
            'course_id' => $validated['course_id'],
            'fields_config' => $fieldsConfig,
            'signatures_config' => $signaturesConfig,
            'font_family' => $validated['font_family'] ?? null,
            'year' => $validated['year'],
        ];

        if ($request->hasFile('template_image')) {
            $data['template_image'] = $request->file('template_image')->store('certificates/templates', 'public');
        }

        $design = CertificateDesign::create($data);

        // Handle signature image uploads
        $this->handleSignatureUploads($request, $design);

        // If students are provided, go to issue step
        $raw = $request->input('student_ids', []);
        $studentIds = is_string($raw) ? json_decode($raw, true) ?? [] : $raw;
        if (!empty($studentIds)) {
            return redirect()->route('admin.students.certificates.issue', [
                'design_id' => $design->id,
                'student_ids' => $studentIds,
                'course_id' => $validated['course_id'],
            ])->with('success', 'تم حفظ التصميم. يمكنك الآن إصدار الشهادات.');
        }

        return redirect()->route('admin.students.certificates.designs')
            ->with('success', 'تم حفظ التصميم بنجاح');
    }

    // ─── Update Design ───

    public function updateDesign(Request $request, $id)
    {
        $design = CertificateDesign::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'course_id' => 'nullable|exists:courses,id',
            'template_image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'fields_config' => 'required|json',
            'signatures_config' => 'nullable|json',
            'font_family' => 'nullable|string|max:100',
            'year' => 'required|integer|min:2000|max:2100',
        ]);

        $data = [
            'name' => $validated['name'],
            'course_id' => $validated['course_id'],
            'fields_config' => json_decode($validated['fields_config'], true),
            'signatures_config' => !empty($validated['signatures_config']) ? json_decode($validated['signatures_config'], true) : null,
            'font_family' => $validated['font_family'] ?? null,
            'year' => $validated['year'],
        ];

        if ($request->hasFile('template_image')) {
            if ($design->template_image) {
                Storage::disk('public')->delete($design->template_image);
            }
            $data['template_image'] = $request->file('template_image')->store('certificates/templates', 'public');
        }

        $design->update($data);

        // Handle signature image uploads
        $this->handleSignatureUploads($request, $design);

        return redirect()->route('admin.students.certificates.designs')
            ->with('success', 'تم تحديث التصميم بنجاح');
    }

    protected function handleSignatureUploads(Request $request, CertificateDesign $design): void
    {
        $signatures = $design->signatures_config ?? [];
        $updated = false;

        foreach ($signatures as $i => $sig) {
            $fieldName = "signature_image_{$i}";
            if ($request->hasFile($fieldName)) {
                if (!empty($sig['image_path'])) {
                    Storage::disk('public')->delete($sig['image_path']);
                }
                $signatures[$i]['image_path'] = $request->file($fieldName)->store('certificates/signatures', 'public');
                $updated = true;
            }
        }

        if ($updated) {
            $design->update(['signatures_config' => $signatures]);
        }
    }

    // ─── Delete Design ───

    public function destroyDesign($id)
    {
        $design = CertificateDesign::findOrFail($id);
        if ($design->template_image) {
            Storage::disk('public')->delete($design->template_image);
        }
        $design->certificates()->delete();
        $design->delete();

        return redirect()->route('admin.students.certificates.designs')
            ->with('success', 'تم حذف التصميم');
    }

    // ─── Issue Form (select design → generate) ───

    public function issue(Request $request)
    {
        $designId = $request->input('design_id');
        $raw = $request->input('student_ids', []);
        $courseId = $request->input('course_id');

        // student_ids can be array (from checkboxes) or JSON string (from designer redirect)
        $studentIds = is_string($raw) ? json_decode($raw, true) ?? [] : $raw;

        $design = $designId ? CertificateDesign::findOrFail($designId) : null;
        $students = Student::whereIn('id', $studentIds)->get();
        $designs = CertificateDesign::orderBy('id', 'desc')->get();
        $courses = Course::orderBy('name_ar')->get();
        $periods = Period::orderBy('name_ar')->get();
        $signatorySets = CertificateSignatorySet::with(['course', 'period', 'center'])->orderBy('name')->get();

        return view('admin.students.certificates.issue', compact(
            'design', 'students', 'studentIds', 'designs', 'courses', 'periods', 'courseId', 'signatorySets'
        ));
    }

    // ─── Generate Certificates ───

    public function generateCertificates(Request $request)
    {
        // student_ids may be JSON string (from hidden input) or array (from individual inputs)
        $raw = $request->input('student_ids', []);
        if (is_string($raw)) {
            $request->merge(['student_ids' => json_decode($raw, true) ?? []]);
        }

        $validated = $request->validate([
            'design_id' => 'required|exists:certificate_designs,id',
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:students,id',
            'course_id' => 'nullable|exists:courses,id',
            'period_id' => 'nullable|exists:periods,id',
            'signatory_set_id' => 'nullable|exists:certificate_signatory_sets,id',
        ]);

        $design = CertificateDesign::findOrFail($validated['design_id']);
        $students = Student::whereIn('id', $validated['student_ids'])->get();
        $selectedSet = !empty($validated['signatory_set_id'])
            ? CertificateSignatorySet::find($validated['signatory_set_id'])
            : null;
        $generated = 0;
        $unresolvedCount = 0;

        $certIds = [];

        DB::beginTransaction();
        try {
            foreach ($students as $student) {
                $certNumber = CertificateNumberSequence::nextNumber($design->year);

                $enrollment = null;
                if ($validated['course_id'] && $validated['period_id']) {
                    $enrollment = StudentEnrollment::where('student_id', $student->id)
                        ->where('course_id', $validated['course_id'])
                        ->where('period_id', $validated['period_id'])
                        ->first();
                } elseif ($validated['course_id']) {
                    $enrollment = StudentEnrollment::where('student_id', $student->id)
                        ->where('course_id', $validated['course_id'])
                        ->first();
                }

                $set = $selectedSet ?? CertificateSignatorySet::resolve(
                    $validated['course_id'] ?? null,
                    $validated['period_id'] ?? $enrollment?->period_id,
                    $student->center_id,
                );

                if (!$set) {
                    $unresolvedCount++;
                }

                $hash = hash('sha256', $student->id . $certNumber . ($enrollment?->id ?? '') . config('app.key'));

                $cert = Certificate::create([
                    'certificate_number' => $certNumber,
                    'design_id' => $design->id,
                    'student_id' => $student->id,
                    'enrollment_id' => $enrollment?->id,
                    'signatory_set_id' => $set?->id,
                    'barcode_hash' => $hash,
                    'issue_date' => now(),
                ]);

                $certIds[] = $cert->id;
                $generated++;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'فشل إصدار الشهادات: ' . $e->getMessage());
        }

        $successMessage = "تم إصدار {$generated} شهادة بنجاح";
        if ($unresolvedCount > 0) {
            $successMessage .= " — تنبيه: {$unresolvedCount} شهادة بدون مجموعة توقيعات مطابقة";
        }

        return redirect()->route('admin.students.certificates.print-batch', [
            'ids' => implode(',', $certIds),
        ])->with('success', $successMessage);
    }

    // ─── Batch Print ───

    public function printBatch(Request $request)
    {
        $designId = $request->input('design_id');
        $certificateIds = $request->input('ids');

        $query = Certificate::with(['student.center', 'design', 'enrollment.course', 'enrollment.period', 'signatorySet.instructorSigner', 'signatorySet.centerManagerSigner', 'signatorySet.projectManagerSigner'])
            ->whereNull('cancelled_at');

        if ($designId) {
            $query->where('design_id', $designId);
        }

        if ($certificateIds) {
            $ids = is_string($certificateIds) ? explode(',', $certificateIds) : $certificateIds;
            $query->whereIn('id', $ids);
        }

        $certificates = $query->orderBy('id', 'desc')->get();

        if ($certificates->isEmpty()) {
            return redirect()->route('admin.students.certificates.index')
                ->with('error', 'لا توجد شهادات للطباعة');
        }

        return view('admin.students.certificates.print-batch', compact('certificates'));
    }

    // ─── Preview Certificate ───

    public function preview($id)
    {
        $certificate = Certificate::with(['student.center', 'design', 'enrollment.course', 'enrollment.period', 'signatorySet.instructorSigner', 'signatorySet.centerManagerSigner', 'signatorySet.projectManagerSigner'])->findOrFail($id);

        return view('admin.students.certificates.preview', compact('certificate'));
    }

    // ─── Public Verification ───

    public function verifyCertificate($hash)
    {
        $certificate = Certificate::with(['student.center', 'design', 'enrollment.course', 'enrollment.period', 'signatorySet.instructorSigner', 'signatorySet.centerManagerSigner', 'signatorySet.projectManagerSigner'])
            ->where('barcode_hash', $hash)
            ->whereNull('cancelled_at')
            ->first();

        if (!$certificate) {
            abort(404, 'الشهادة غير موجودة أو تم إلغاؤها');
        }

        if (!$certificate->is_verified) {
            $certificate->update([
                'is_verified' => true,
                'verified_at' => now(),
            ]);
        }

        return view('admin.students.certificates.verify', compact('certificate'));
    }

    public function publicPreview($hash)
    {
        $certificate = Certificate::with(['student.center', 'design', 'enrollment.course', 'enrollment.period', 'signatorySet.instructorSigner', 'signatorySet.centerManagerSigner', 'signatorySet.projectManagerSigner'])
            ->where('barcode_hash', $hash)
            ->whereNull('cancelled_at')
            ->firstOrFail();

        return view('admin.students.certificates.preview', compact('certificate'));
    }

    public function cancel($id)
    {
        $certificate = Certificate::findOrFail($id);
        $certificate->update(['cancelled_at' => now()]);

        return redirect()->route('admin.students.certificates.index')
            ->with('success', 'تم إلغاء الشهادة رقم ' . $certificate->certificate_number);
    }
}
