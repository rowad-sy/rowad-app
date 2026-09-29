<?php

namespace App\Http\Controllers\Admin\Logistics;

use App\Http\Controllers\Controller;
use App\Models\Admin\Center;
use App\Models\Admin\Logistics\Asset;
use App\Models\Admin\Project;
use App\Models\User;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\Logistics\Asset,view')->only(['index', 'show']);
        $this->middleware('permission:App\Models\Admin\Logistics\Asset,create')->only(['create', 'store']);
        $this->middleware('permission:App\Models\Admin\Logistics\Asset,edit')->only(['edit', 'update']);
        $this->middleware('permission:App\Models\Admin\Logistics\Asset,delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $query = Asset::with(['center', 'project', 'recipient']);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('center_id')) {
            $query->where('center_id', $request->center_id);
        }

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        $assets = $query->orderBy('created_at', 'desc')
            ->paginate(15)
            ->appends($request->only(['type', 'status', 'center_id', 'project_id']));

        $types = Asset::select('type')->distinct()->pluck('type');
        $statuses = Asset::select('status')->distinct()->pluck('status');
        $centers = Center::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();

        return view('admin.logistics.assets.index', compact('assets', 'types', 'statuses', 'centers', 'projects'));
    }

    /** عرض قراءة فقط لبيانات الأصل نفسها (لا وظيفة جديدة): نفس صلاحية العرض التي تحكم القائمة. */
    public function show(Asset $asset)
    {
        $asset->load(['center', 'project', 'recipient']);

        return view('admin.logistics.assets.show', compact('asset'));
    }

    public function create()
    {
        $centers = Center::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        $users = User::orderBy('name')->get();

        return view('admin.logistics.assets.form', compact('centers', 'projects', 'users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'asset_code' => 'required|string|max:255|unique:logistics_assets,asset_code',
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|max:255',
            'center_id' => 'nullable|exists:centers,id',
            'project_id' => 'nullable|exists:projects,id',
            'room_number' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:1000',
            'recipient_id' => 'nullable|exists:users,id',
        ]);

        Asset::create($validated);

        return redirect()->route('admin.logistics.assets.index')
            ->with('success', 'تم إضافة الأصل بنجاح');
    }

    public function edit(Asset $asset)
    {
        $centers = Center::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        $users = User::orderBy('name')->get();

        return view('admin.logistics.assets.form', compact('asset', 'centers', 'projects', 'users'));
    }

    public function update(Request $request, Asset $asset)
    {
        $validated = $request->validate([
            'asset_code' => ['required', 'string', 'max:255', \Illuminate\Validation\Rule::unique('logistics_assets', 'asset_code')->ignore($asset->id)],
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|max:255',
            'center_id' => 'nullable|exists:centers,id',
            'project_id' => 'nullable|exists:projects,id',
            'room_number' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:1000',
            'recipient_id' => 'nullable|exists:users,id',
        ]);

        $asset->update($validated);

        return redirect()->route('admin.logistics.assets.index')
            ->with('success', 'تم تحديث الأصل بنجاح');
    }

    public function destroy(Asset $asset)
    {
        $asset->delete();

        return redirect()->route('admin.logistics.assets.index')
            ->with('success', 'تم حذف الأصل بنجاح');
    }
}
