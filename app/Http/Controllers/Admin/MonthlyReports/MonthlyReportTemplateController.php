<?php

namespace App\Http\Controllers\Admin\MonthlyReports;

use App\Http\Controllers\Controller;
use App\Models\Admin\MonthlyReports\MonthlyReportTemplate;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MonthlyReportTemplateController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\MonthlyReports\MonthlyReportTemplate,view')->only(['index']);
        $this->middleware('permission:App\Models\Admin\MonthlyReports\MonthlyReportTemplate,create')->only(['create', 'store']);
        $this->middleware('permission:App\Models\Admin\MonthlyReports\MonthlyReportTemplate,edit')->only(['edit', 'update']);
        $this->middleware('permission:App\Models\Admin\MonthlyReports\MonthlyReportTemplate,delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $search = $request->input('search');

        $templates = MonthlyReportTemplate::withCount('reports')
            ->when($search, fn ($q, $v) => $q->where('title_ar', 'like', "%{$v}%")
                ->orWhere('key', 'like', "%{$v}%"))
            ->orderBy('title_ar')
            ->paginate(15)
            ->withQueryString();

        return view('admin.monthly-reports.templates.index', compact('templates', 'search'));
    }

    public function create()
    {
        return view('admin.monthly-reports.templates.form');
    }

    public function store(Request $request)
    {
        $data = $this->validateTemplate($request);
        $definition = $this->parseDefinition($request->input('json_definition'));

        MonthlyReportTemplate::create($data + [
            'version' => 1,
            'json_definition' => $definition,
        ]);

        return redirect()->route('admin.monthly-reports.templates.index')
            ->with('success', 'تمت إضافة قالب التقرير الشهري بنجاح');
    }

    public function edit(MonthlyReportTemplate $template)
    {
        return view('admin.monthly-reports.templates.form', compact('template'));
    }

    public function update(Request $request, MonthlyReportTemplate $template)
    {
        $data = $this->validateTemplate($request, $template->id);
        $definition = $this->parseDefinition($request->input('json_definition'));

        if ($definition !== $template->json_definition) {
            $data['json_definition'] = $definition;
            $data['version'] = $template->version + 1;
        }

        $template->update($data);

        return redirect()->route('admin.monthly-reports.templates.index')
            ->with('success', 'تم تحديث قالب التقرير الشهري بنجاح');
    }

    public function destroy(MonthlyReportTemplate $template)
    {
        if ($template->reports()->exists()) {
            return back()->with('error', 'لا يمكن حذف قالب مستخدم في تقارير موجودة');
        }

        $template->delete();

        return redirect()->route('admin.monthly-reports.templates.index')
            ->with('success', 'تم حذف قالب التقرير الشهري بنجاح');
    }

    private function validateTemplate(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'key' => 'required|string|max:100|unique:monthly_report_templates,key' . ($ignoreId ? ",{$ignoreId}" : ''),
            'title_ar' => 'required|string|max:200',
            'slug' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
        ]);
    }

    private function parseDefinition(string $json): array
    {
        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            throw ValidationException::withMessages([
                'json_definition' => 'صيغة JSON غير صحيحة',
            ]);
        }

        if (! isset($decoded['sections']) || ! is_array($decoded['sections']) || empty($decoded['sections'])) {
            throw ValidationException::withMessages([
                'json_definition' => 'يجب أن يحتوي التعريف على مصفوفة sections غير فارغة',
            ]);
        }

        return $decoded;
    }
}