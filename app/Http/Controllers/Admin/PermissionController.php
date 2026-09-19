<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Center;
use App\Models\Admin\Cohort;
use App\Models\Admin\Group;
use App\Models\Admin\Permission;
use App\Models\Admin\Project;
use App\Models\User;
use App\Support\PermissionModelCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\Permission,view')->only(['index', 'show']);
        $this->middleware('permission:App\Models\Admin\Permission,create')->only(['create', 'store']);
        $this->middleware('permission:App\Models\Admin\Permission,edit')->only(['edit', 'update']);
        $this->middleware('permission:App\Models\Admin\Permission,delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $permissions = Permission::with(['user', 'group', 'center', 'project', 'cohort'])
            ->when($search, function ($q, $search) {
                return $q->where(function ($q) use ($search) {
                    $q->whereHas('user', fn($q) => $q->where('name', 'like', "%{$search}%"))
                      ->orWhereHas('group', fn($q) => $q->where('name', 'like', "%{$search}%"));
                });
            })->orderBy('id', 'desc')->paginate(10);

        return view('admin.permissions.index', compact('permissions', 'search'));
    }

    public function create()
    {
        return view('admin.permissions.form', $this->formData());
    }

    public function store(Request $request)
    {
        $validated = $this->validateMatrix($request);
        $scope = $this->scopeFromRequest($validated);

        $count = 0;
        foreach ($validated['rows'] as $rowIndex => $row) {
            if ($row['assign_to'] === 'user') {
                $count += $this->syncEntity('user_id', $row['user_id'], $validated['perms'][$rowIndex] ?? [], $scope);
            } else {
                $count += $this->syncEntity('group_id', $row['group_id'], $validated['perms'][$rowIndex] ?? [], $scope);
            }
        }

        return redirect()->route('admin.permissions.index')
            ->with('success', $count > 0 ? 'تم إضافة الصلاحيات بنجاح' : 'لم يتم تحديد أي صلاحية');
    }

    public function edit(Permission $permission)
    {
        $data = $this->formData();
        $data['permission'] = $permission;

        $isUser = $permission->user_id !== null;
        $entityId = $isUser ? $permission->user_id : $permission->group_id;

        $recordScope = $this->scopeFromRecord($permission);
        $data['scope'] = [
            'center_id' => $recordScope['center_id'],
            'project_id' => $recordScope['project_id'],
            'cohort_id' => $recordScope['cohort_id'],
        ];

        $data['rows'][] = [
            'assign_to' => $isUser ? 'user' : 'group',
            'user_id' => $isUser ? $entityId : null,
            'group_id' => $isUser ? null : $entityId,
            'label' => $isUser ? ($permission->user?->name ?? 'مستخدم') : ($permission->group?->name ?? 'مجموعة'),
            'perms' => $this->flagsForEntity($isUser ? 'user_id' : 'group_id', $entityId, $data['scope']),
        ];

        return view('admin.permissions.form', $data);
    }

    public function update(Request $request, Permission $permission)
    {
        $validated = $this->validateMatrix($request);
        $scope = $this->scopeFromRequest($validated);

        $count = 0;
        foreach ($validated['rows'] as $rowIndex => $row) {
            if ($row['assign_to'] === 'user') {
                $count += $this->syncEntity('user_id', $row['user_id'], $validated['perms'][$rowIndex] ?? [], $scope);
            } else {
                $count += $this->syncEntity('group_id', $row['group_id'], $validated['perms'][$rowIndex] ?? [], $scope);
            }
        }

        return redirect()->route('admin.permissions.index')
            ->with('success', 'تم تحديث الصلاحيات بنجاح');
    }

    public function destroy(Permission $permission)
    {
        $permission->delete();

        return redirect()->route('admin.permissions.index')
            ->with('success', 'تم حذف الصلاحية بنجاح');
    }

    /*
     * --------------------------------------------
     * بيانات ومصفوفة النموذج
     * --------------------------------------------
     */

    private function formData(): array
    {
        return [
            'users' => User::orderBy('name')->get(),
            'groups' => Group::orderBy('name')->get(),
            'centers' => Center::orderBy('name')->get(),
            'projects' => Project::orderBy('name')->get(),
            'cohorts' => Cohort::with('project')->orderBy('name')->get(),
            'modelGroups' => PermissionModelCatalog::groups(),
            'modelKeys' => PermissionModelCatalog::keys(),
            'rows' => [],
            'scope' => [
                'center_id' => null,
                'project_id' => null,
                'cohort_id' => null,
            ],
        ];
    }

    private function validateMatrix(Request $request): array
    {
        return $request->validate([
            'rows' => 'required|array|min:1',
            'rows.*.assign_to' => 'required|in:user,group',
            'rows.*.user_id' => 'required_if:rows.*.assign_to,user|nullable|exists:users,id',
            'rows.*.group_id' => 'required_if:rows.*.assign_to,group|nullable|exists:groups,id',
            'perms' => 'nullable|array',
            'center_id' => 'nullable|exists:centers,id',
            'project_id' => 'nullable|exists:projects,id',
            'cohort_id' => 'nullable|exists:cohorts,id',
        ]);
    }

    private function scopeFromRequest(array $validated): array
    {
        return [
            'center_id' => $validated['center_id'] ?? null,
            'project_id' => $validated['project_id'] ?? null,
            'cohort_id' => $validated['cohort_id'] ?? null,
        ];
    }

    private function scopeFromRecord(Permission $permission): array
    {
        return [
            'center_id' => $permission->center_id,
            'project_id' => $permission->project_id,
            'cohort_id' => $permission->cohort_id,
        ];
    }

    /*
     * قراءة حالات الخلايا الحالية لكيان (مستخدم/مجموعة) ضمن النطاق المعروض فقط:
     * النطاقات الأخرى تُحفظ كما هي ولا تُظهر في الشبكة.
     */
    private function flagsForEntity(string $column, int $entityId, array $scope): array
    {
        $flags = [];

        $this->scopeQuery(Permission::query(), $scope)
            ->where($column, $entityId)
            ->whereNull('model_id')
            ->get()
            ->each(function (Permission $record) use (&$flags) {
                foreach ($record->model_names ?? [] as $model) {
                    foreach (['can_view', 'can_create', 'can_edit', 'can_delete'] as $flag) {
                        $flags[$model][$flag] = ($flags[$model][$flag] ?? false) || $record->$flag;
                    }
                }
            });

        $keyed = [];
        foreach ($flags as $model => $rowFlags) {
            $modelKey = PermissionModelCatalog::keyFor($model);
            if ($modelKey !== null) {
                $keyed[$modelKey] = $rowFlags;
            }
        }

        return $keyed;
    }

    /*
     * تقسيم المدخلات من الشبكة إلى سجل واحد لكل (تعيين × موديل) ضمن النطاق المشترك.
     *
     * المواءمة (reconcile):
     *  - السجلات القديمة متعددة الموديلات تُقسم عند مواجهتها (فكّ تصاعدي).
     *  - كل خلية محددة تُحدّث/تُنشأ كسجل بموديل واحد وأعلامه الخاصة.
     *  - الخلايا غير المحددة تُحذف من السجلات الموجودة (ضمن النطاق المعروض فقط).
     */
    private function syncEntity(string $column, int $entityId, array $matrixRow, array $scope): int
    {
        $columns = ['can_view', 'can_create', 'can_edit', 'can_delete'];
        $models = PermissionModelCatalog::all();

        $existing = $this->scopeQuery(Permission::query(), $scope)
            ->where($column, $entityId)
            ->whereNull('model_id')
            ->get();

        $existingByModel = [];

        foreach ($existing as $record) {
            $names = array_values($record->model_names ?? []);

            if (count($names) > 1) {
                foreach ($names as $name) {
                    if (! $this->sameRecordExists($column, $entityId, $scope, $name, $record)) {
                        Permission::create($this->recordData($column, $entityId, $scope, $name, $record));
                    }
                }
                $record->delete();
                continue;
            }

            if (count($names) === 1) {
                $existingByModel[$names[0]] = $record;
            }
        }

        $desiredModels = [];
        $count = 0;

        foreach ($matrixRow as $modelKey => $cell) {
            $model = $models[$modelKey]['model'] ?? null;
            if ($model === null || ! is_array($cell)) {
                continue;
            }

            $flags = [];
            foreach ($columns as $flag) {
                $flags[$flag] = filter_var($cell[$flag] ?? false, FILTER_VALIDATE_BOOL);
            }

            if (! array_filter($flags)) {
                continue;
            }

            $desiredModels[] = $model;

            if (isset($existingByModel[$model])) {
                $existingByModel[$model]->update($flags);
            } else {
                Permission::create($this->recordData($column, $entityId, $scope, $model, flags: $flags));
            }

            $count++;
        }

        // حذف السجلات الموجودة ضمن النطاق للموديلات غير المحددة
        foreach ($existingByModel as $model => $record) {
            if (! in_array($model, $desiredModels, true)) {
                $record->delete();
            }
        }

        return $count;
    }

    private function sameRecordExists(string $column, int $entityId, array $scope, string $model, Permission $exclude): bool
    {
        $candidates = $this->scopeQuery(Permission::query(), $scope)
            ->where($column, $entityId)
            ->where('id', '!=', $exclude->id)
            ->whereJsonContains('model_names', $model)
            ->get();

        return $candidates->contains(fn (Permission $p) => count($p->model_names ?? []) === 1);
    }

    private function recordData(
        string $column,
        int $entityId,
        array $scope,
        string $model,
        ?Permission $source = null,
        array $flags = []
    ): array {
        if ($source !== null) {
            $flags = [
                'can_view' => $source->can_view,
                'can_create' => $source->can_create,
                'can_edit' => $source->can_edit,
                'can_delete' => $source->can_delete,
            ];
        }

        return array_merge([
            $column => $entityId,
            'model_names' => [$model],
            'model_id' => $source?->model_id,
            'center_id' => $scope['center_id'],
            'project_id' => $scope['project_id'],
            'cohort_id' => $scope['cohort_id'],
        ], $flags);
    }

    private function scopeQuery(Builder $query, array $scope): Builder
    {
        return $query
            ->where('center_id', $scope['center_id'])
            ->where('project_id', $scope['project_id'])
            ->where('cohort_id', $scope['cohort_id']);
    }
}