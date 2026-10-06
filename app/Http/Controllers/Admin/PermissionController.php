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
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

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

        $records = Permission::with(['user', 'group', 'center', 'project', 'cohort'])
            ->when($search, function ($q, $search) {
                return $q->where(function ($q) use ($search) {
                    $q->whereHas('user', fn($q) => $q->where('name', 'like', "%{$search}%"))
                      ->orWhereHas('group', fn($q) => $q->where('name', 'like', "%{$search}%"));
                });
            })->orderBy('id', 'desc')->get();

        $entities = $records
            ->groupBy(fn(Permission $p) => $p->user_id !== null ? 'u'.$p->user_id : 'g'.$p->group_id)
            ->map(function ($entityRecords) {
                $first = $entityRecords->first();

                $scopes = $entityRecords
                    ->groupBy(fn(Permission $p) => $p->center_id.'-'.$p->project_id.'-'.$p->cohort_id)
                    ->values()
                    ->map(function ($scopeRecords) {
                        return [
                            'representative' => $scopeRecords->sortByDesc('id')->first(),
                            'models' => $scopeRecords->flatMap(fn(Permission $p) => $p->model_names ?? [])->unique()->values(),
                            'flags' => [
                                'can_view' => $scopeRecords->contains(fn(Permission $p) => (bool) $p->can_view),
                                'can_create' => $scopeRecords->contains(fn(Permission $p) => (bool) $p->can_create),
                                'can_edit' => $scopeRecords->contains(fn(Permission $p) => (bool) $p->can_edit),
                                'can_delete' => $scopeRecords->contains(fn(Permission $p) => (bool) $p->can_delete),
                            ],
                        ];
                    });

                return [
                    'is_user' => $first->user_id !== null,
                    'user' => $first->user,
                    'group' => $first->group,
                    'scopes' => $scopes,
                ];
            })
            ->values();

        $counts = [
            'all' => $entities->count(),
            'user' => $entities->where('is_user', true)->count(),
            'role' => $entities->filter(fn($e) => !$e['is_user'] && $e['group'] && $e['group']->isRole())->count(),
            'group' => $entities->filter(fn($e) => !$e['is_user'] && $e['group'] && !$e['group']->isRole())->count(),
        ];

        $entityFilter = in_array($request->input('entity'), ['user', 'role', 'group'], true)
            ? $request->input('entity')
            : 'all';

        if ($entityFilter === 'user') {
            $entities = $entities->where('is_user', true);
        } elseif ($entityFilter === 'role') {
            $entities = $entities->filter(fn($e) => !$e['is_user'] && $e['group'] && $e['group']->isRole());
        } elseif ($entityFilter === 'group') {
            $entities = $entities->filter(fn($e) => !$e['is_user'] && $e['group'] && !$e['group']->isRole());
        }

        $entities = $entities->values();

        $page = max(1, (int) $request->query('page', 1));
        $perPage = 10;

        $permissions = new LengthAwarePaginator(
            $entities->slice(($page - 1) * $perPage, $perPage)->values()->all(),
            $entities->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('admin.permissions.index', compact('permissions', 'search', 'entityFilter', 'counts'));
    }

    public function create()
    {
        return view('admin.permissions.form', $this->formData());
    }

    public function store(Request $request)
    {
        $validated = $this->validateMatrix($request);
        $this->guardGrant($validated);

        /*
         * الترتيبة v2: شاشة الصلاحيات لا تحمل نطاقات إطلاقاً —
         * التعيين هنا «ماذا» (موديلات + أعلام)، و«أين» يُقرَّر وقت إسناد الدور
         * في /admin/roles أو شاشة المستخدم. السجلات القديمة الحاملة لنطاق
         * تبقى محترمة حتى تُنقَّى (أمر permissions:audit-scopes).
         */
        $scope = ['center_id' => null, 'project_id' => null, 'cohort_id' => null];

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
            'assign_to' => $isUser ? 'user' : ($permission->group?->isRole() ? 'role' : 'group'),
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
        $this->guardGrant($validated);

        /*
         * الترتيبة v2: النطاق لا يُقرأ من الطلب بعد الآن —
         * يُورَّث من السجل المسند نفسه (grandfathered) حتى لا تفقد
         * منحٌ قديمة حملت نطاقاً ذلك النطاقَ عند أي تعديل.
         */
        $scope = $this->scopeFromRecord($permission);

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
        $this->entityScopeQuery($permission)->delete();

        return redirect()->route('admin.permissions.index')
            ->with('success', 'تم حذف صلاحيات العنصر ضمن هذا النطاق بنجاح');
    }

    /*
     * كل السجلات المنتمية لنفس العنصر (مستخدم/مجموعة) ونفس النطاق.
     */
    private function entityScopeQuery(Permission $permission): Builder
    {
        $query = Permission::query();

        if ($permission->user_id !== null) {
            $query->where('user_id', $permission->user_id);
        } else {
            $query->where('group_id', $permission->group_id);
        }

        foreach (['center_id', 'project_id', 'cohort_id'] as $column) {
            $value = $permission->$column;
            $value === null ? $query->whereNull($column) : $query->where($column, $value);
        }

        return $query;
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
            'groups' => Group::orderByDesc('kind')->orderBy('name')->get(), // الأدوار (role > group alphabetically) أولاً
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
        $validated = $request->validate([
            'rows' => 'required|array|min:1',
            'rows.*.assign_to' => 'required|in:user,group,role',
            'rows.*.user_id' => 'required_if:rows.*.assign_to,user|nullable|exists:users,id',
            'rows.*.group_id' => 'required_if:rows.*.assign_to,group,role|nullable|exists:groups,id',
            'perms' => 'nullable|array',
        ]);

        /*
         * مطابقة النوع: «دور» يجب أن يشيّر على kind=role و«مجموعة» على kind=group —
         * يمنع الخلط البصري بين النوعين في نفس قائمة الاختيار.
         */
        if (!empty($validated['rows'])) {
            $groups = Group::whereIn('id', collect($validated['rows'])->pluck('group_id')->filter()->all())->get()->keyBy('id');

            foreach ($validated['rows'] as $index => $row) {
                $assignTo = $row['assign_to'] ?? '';
                $group = $groups->get($row['group_id'] ?? null);

                if ($group && $assignTo === 'role' && !$group->isRole()) {
                    throw ValidationException::withMessages([
                        "rows.$index.group_id" => 'العنصر المحدد «مجموعة» وليست دوراً — غيّر النوع أو اختر دوراً.',
                    ]);
                }

                if ($group && $assignTo === 'group' && $group->isRole()) {
                    throw ValidationException::withMessages([
                        "rows.$index.group_id" => 'العنصر المحدد «دور» وليس مجموعة — استخدم نوع «دور».',
                    ]);
                }
            }
        }

        return $validated;
    }

    /*
     * ضوابط منح الصلاحيات (W4) — لغير السوبر-أدن:
     *  - لا يعدّل صلاحيات حسابه الخاص (منع التصعيد الذاتي).
     *  - لا يمنح على حسابات السوبر-أدن.
     *  - لا يمنح صلاحيات على موديلات الإدارة العليا (مستخدمون/صلاحيات/مجموعات).
     */
    private function guardGrant(array $validated): void
    {
        $actor = auth()->user();

        if ($actor->type === 'super-admin') {
            return;
        }

        $metaModels = ['App\\Models\\User', 'App\\Models\\Admin\\Permission', 'App\\Models\\Admin\\Group'];
        $models = PermissionModelCatalog::all();

        foreach ($validated['rows'] as $rowIndex => $row) {
            if ($row['assign_to'] === 'user') {
                if ((int) $row['user_id'] === (int) $actor->id) {
                    abort(403, 'لا يمكنك تعديل صلاحيات حسابك الخاص.');
                }

                if (User::find($row['user_id'])?->type === 'super-admin') {
                    abort(403, 'لا يمكن منح الصلاحيات مباشرة لحساب سوبر-أدن.');
                }
            }

            foreach ($validated['perms'][$rowIndex] ?? [] as $modelKey => $cell) {
                if (!is_array($cell)) {
                    continue;
                }

                $active = array_filter(
                    ['can_view', 'can_create', 'can_edit', 'can_delete'],
                    fn($flag) => filter_var($cell[$flag] ?? false, FILTER_VALIDATE_BOOL)
                );

                if (!$active) {
                    continue;
                }

                $model = $models[$modelKey]['model'] ?? null;

                if ($model !== null && in_array($model, $metaModels, true)) {
                    abort(403, "لا يمكنك منح صلاحيات على موديلات الإدارة العليا ({$model}) — مخصصة للسوبر-أدن.");
                }
            }
        }
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