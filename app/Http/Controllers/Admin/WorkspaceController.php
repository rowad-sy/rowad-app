<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\PermissionHelper;
use App\Http\Controllers\Controller;
use App\Models\Admin\Hr\Employee;
use App\Support\WorkspaceRegistry;
use Illuminate\Http\Request;

class WorkspaceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $modules = collect(WorkspaceRegistry::modules())
            ->map(function (array $module) use ($user): array {
                $module['actions'] = collect($module['actions'])
                    ->filter(fn (array $action): bool => PermissionHelper::can($user, $action['model'], $action['action']))
                    ->values()
                    ->all();

                return $module;
            })
            ->filter(fn (array $module): bool => $module['actions'] !== [])
            ->values()
            ->all();

        $employee = Employee::with(['center', 'project'])->where('user_id', $user->id)->first();
        $groupNames = $user->groups->pluck('name')->all();

        return view('admin.workspace.index', compact('modules', 'employee', 'groupNames'));
    }
}
