<?php

namespace App\Http\Controllers\Admin\Student;

use App\Http\Controllers\Controller;
use App\Models\Admin\Center;
use App\Models\Admin\Student\CertificateSignatorySet;
use App\Models\Admin\Student\CertificateSigner;
use App\Models\Admin\Student\Course;
use App\Models\Admin\Student\Period;
use Illuminate\Http\Request;

class CertificateSignatorySetController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\Student\Certificate,view')->only(['index']);
        $this->middleware('permission:App\Models\Admin\Student\Certificate,create')->only(['create', 'store', 'edit', 'update']);
        $this->middleware('permission:App\Models\Admin\Student\Certificate,delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $perPage = (int) $request->input('per_page', 15);

        $sets = CertificateSignatorySet::with(['course', 'period', 'center', 'instructorSigner', 'centerManagerSigner', 'projectManagerSigner'])
            ->withCount('certificates')
            ->when($search, fn($q, $v) => $q->where('name', 'like', "%{$v}%"))
            ->orderBy('id', 'desc')
            ->paginate($perPage)
            ->appends($request->only(['search', 'per_page']));

        return view('admin.students.certificates.signatory-sets.index', compact('sets', 'search'));
    }

    public function create()
    {
        return view('admin.students.certificates.signatory-sets.form', $this->formData());
    }

    public function store(Request $request)
    {
        CertificateSignatorySet::create($this->validated($request));

        return redirect()->route('admin.students.certificates.signatory-sets.index')
            ->with('success', 'تم إضافة مجموعة التوقيعات بنجاح');
    }

    public function edit(CertificateSignatorySet $set)
    {
        return view('admin.students.certificates.signatory-sets.form', array_merge(
            $this->formData(), ['set' => $set]
        ));
    }

    public function update(Request $request, CertificateSignatorySet $set)
    {
        $set->update($this->validated($request));

        return redirect()->route('admin.students.certificates.signatory-sets.index')
            ->with('success', 'تم تحديث مجموعة التوقيعات بنجاح');
    }

    public function destroy(CertificateSignatorySet $set)
    {
        $set->delete();

        return redirect()->route('admin.students.certificates.signatory-sets.index')
            ->with('success', 'تم حذف مجموعة التوقيعات. الشهادات المرتبطة بها فقدت الموقعين فقط.');
    }

    protected function formData(): array
    {
        return [
            'courses' => Course::orderBy('name_ar')->get(),
            'periods' => Period::orderBy('name_ar')->get(),
            'centers' => Center::orderBy('name')->get(),
            'signersByRole' => [
                'instructor' => CertificateSigner::role('instructor')->orderBy('name_ar')->get(),
                'center_manager' => CertificateSigner::role('center_manager')->orderBy('name_ar')->get(),
                'project_manager' => CertificateSigner::role('project_manager')->orderBy('name_ar')->get(),
            ],
        ];
    }

    protected function validated(Request $request): array
    {
        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'course_id' => 'nullable|exists:courses,id',
            'period_id' => 'nullable|exists:periods,id',
            'center_id' => 'nullable|exists:centers,id',
            'instructor_signer_id' => 'nullable|exists:certificate_signers,id',
            'center_manager_signer_id' => 'nullable|exists:certificate_signers,id',
            'project_manager_signer_id' => 'nullable|exists:certificate_signers,id',
        ]);

        return $validated;
    }
}
