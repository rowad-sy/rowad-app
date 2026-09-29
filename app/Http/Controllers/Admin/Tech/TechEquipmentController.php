<?php

namespace App\Http\Controllers\Admin\Tech;

use App\Http\Controllers\Controller;
use App\Models\Admin\Center;
use App\Models\Admin\Hr\Employee;
use App\Models\Admin\Project;
use App\Models\Admin\Tech\TechEquipment;
use App\Support\RecordAccess;
use Illuminate\Http\Request;

class TechEquipmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\Tech\TechEquipment,view')->only(['index', 'show']);
        $this->middleware('permission:App\Models\Admin\Tech\TechEquipment,create')->only(['create', 'store']);
        $this->middleware('permission:App\Models\Admin\Tech\TechEquipment,edit')->only(['edit', 'update']);
        $this->middleware('permission:App\Models\Admin\Tech\TechEquipment,delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $type = $request->input('type');
        $condition = $request->input('condition');
        $perPage = (int) $request->input('per_page', 10);

        $scope = \App\Helpers\PermissionHelper::getEffectiveScope(auth()->user(), 'App\Models\Admin\Tech\TechEquipment');
        $centerId = $request->filled('center_id') ? $request->input('center_id') : (count($scope['center_ids']) === 1 ? $scope['center_ids'][0] : '');
        $projectId = $request->filled('project_id') ? $request->input('project_id') : (count($scope['project_ids']) === 1 ? $scope['project_ids'][0] : '');

        $equipment = TechEquipment::with(['center', 'project'])
            ->when($search, function ($q, $search) {
                return $q->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('serial_number', 'like', "%{$search}%");
                });
            })
            ->when($type && $type !== 'all', fn ($q) => $q->where('type', $type))
            ->when($condition && $condition !== 'all', fn ($q) => $q->where('condition', $condition))
            ->when($centerId, fn ($q) => $q->where('center_id', $centerId))
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId))
            ->when(!$scope['sees_all'] && !empty($scope['center_ids']), fn ($q) => $q->whereIn('center_id', $scope['center_ids']))
            ->when(!$scope['sees_all'] && !empty($scope['project_ids']), fn ($q) => $q->whereIn('project_id', $scope['project_ids']))
            ->orderBy('id', 'desc')
            ->paginate($perPage)
            ->appends($request->only(['search', 'type', 'condition', 'center_id', 'project_id', 'per_page']));

        $centers = Center::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();

        return view('admin.tech.equipment.index', compact('equipment', 'search', 'type', 'condition', 'centerId', 'projectId', 'perPage', 'centers', 'projects'));
    }

    public function create()
    {
        $centers = Center::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();

        $userEmployee = Employee::where('user_id', auth()->id())->first();
        $defaultCenterId = $userEmployee?->center_id;
        $defaultProjectId = $userEmployee?->project_id;

        return view('admin.tech.equipment.form', compact('centers', 'projects', 'defaultCenterId', 'defaultProjectId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:100',
            'serial_number' => 'nullable|string|max:100',
            'condition' => 'required|in:a,b,c,d,e',
            'room' => 'nullable|string|max:100',
            'center_id' => 'nullable|exists:centers,id',
            'project_id' => 'nullable|exists:projects,id',
            'notes' => 'nullable|string',
        ]);

        RecordAccess::authorizeTarget(TechEquipment::class, 'create', $validated['center_id'] ?? null, $validated['project_id'] ?? null);
        TechEquipment::create($validated);

        return redirect()->route('admin.tech.equipment.index')
            ->with('success', 'تم إضافة المعدة بنجاح');
    }

    public function show(TechEquipment $equipment)
    {
        RecordAccess::authorize(TechEquipment::class, 'view', $equipment->center_id, $equipment->project_id, $equipment->id);
        $equipment->load(['center', 'project']);
        return view('admin.tech.equipment.show', compact('equipment'));
    }

    public function edit(TechEquipment $equipment)
    {
        RecordAccess::authorize(TechEquipment::class, 'edit', $equipment->center_id, $equipment->project_id, $equipment->id);
        $centers = Center::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();

        return view('admin.tech.equipment.form', compact('equipment', 'centers', 'projects'));
    }

    public function update(Request $request, TechEquipment $equipment)
    {
        RecordAccess::authorize(TechEquipment::class, 'edit', $equipment->center_id, $equipment->project_id, $equipment->id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:100',
            'serial_number' => 'nullable|string|max:100',
            'condition' => 'required|in:a,b,c,d,e',
            'room' => 'nullable|string|max:100',
            'center_id' => 'nullable|exists:centers,id',
            'project_id' => 'nullable|exists:projects,id',
            'notes' => 'nullable|string',
        ]);

        RecordAccess::authorizeTarget(TechEquipment::class, 'edit', $validated['center_id'] ?? null, $validated['project_id'] ?? null, $equipment->id);
        $equipment->update($validated);

        return redirect()->route('admin.tech.equipment.index')
            ->with('success', 'تم تحديث المعدة بنجاح');
    }

    public function destroy(TechEquipment $equipment)
    {
        RecordAccess::authorize(TechEquipment::class, 'delete', $equipment->center_id, $equipment->project_id, $equipment->id);
        $equipment->delete();

        return redirect()->route('admin.tech.equipment.index')
            ->with('success', 'تم حذف المعدة بنجاح');
    }
}
