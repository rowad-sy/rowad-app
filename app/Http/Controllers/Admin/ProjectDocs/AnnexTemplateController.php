<?php

namespace App\Http\Controllers\Admin\ProjectDocs;

use App\Http\Controllers\Controller;
use App\Models\Admin\ProjectDocs\AnnexTemplate;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AnnexTemplateController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\ProjectDocs\AnnexTemplate,view')->only(['index']);
        $this->middleware('permission:App\Models\Admin\ProjectDocs\AnnexTemplate,create')->only(['create', 'store']);
        $this->middleware('permission:App\Models\Admin\ProjectDocs\AnnexTemplate,edit')->only(['edit', 'update']);
        $this->middleware('permission:App\Models\Admin\ProjectDocs\AnnexTemplate,delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $search = $request->input('search');

        $templates = AnnexTemplate::withCount('documents')
            ->when($search, fn ($q, $v) => $q->where('title_ar', 'like', "%{$v}%")
                ->orWhere('key', 'like', "%{$v}%"))
            ->orderBy('title_ar')
            ->paginate(15)
            ->withQueryString();

        return view('admin.project-docs.templates.index', compact('templates', 'search'));
    }

    public function create()
    {
        return view('admin.project-docs.templates.form');
    }

    public function store(Request $request)
    {
        $data = $this->validateTemplate($request);
        $definition = $this->parseDefinition($request->input('json_definition'));

        AnnexTemplate::create($data + [
            'version' => 1,
            'json_definition' => $definition,
        ]);

        return redirect()->route('admin.project-docs.templates.index')
            ->with('success', 'تم إضافة قالب الوثيقة بنجاح');
    }

    public function edit(AnnexTemplate $template)
    {
        return view('admin.project-docs.templates.form', compact('template'));
    }

    public function update(Request $request, AnnexTemplate $template)
    {
        $data = $this->validateTemplate($request, $template->id);
        $definition = $this->parseDefinition($request->input('json_definition'));

        if ($definition !== $template->json_definition) {
            $data['json_definition'] = $definition;
            $data['version'] = $template->version + 1;
        }

        $template->update($data);

        return redirect()->route('admin.project-docs.templates.index')
            ->with('success', 'تم تحديث قالب الوثيقة بنجاح');
    }

    public function destroy(AnnexTemplate $template)
    {
        if ($template->documents()->exists()) {
            return back()->with('error', 'لا يمكن حذف قالب مستخدم في وثائق موجودة');
        }

        $template->delete();

        return redirect()->route('admin.project-docs.templates.index')
            ->with('success', 'تم حذف قالب الوثيقة بنجاح');
    }

    private function validateTemplate(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'key' => 'required|string|max:100|unique:annex_templates,key' . ($ignoreId ? ",{$ignoreId}" : ''),
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