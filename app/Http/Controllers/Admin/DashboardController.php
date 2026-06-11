<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Helpers\PermissionHelper;
use App\Models\Admin\Center;
use App\Models\Admin\Group;
use App\Models\Admin\Project;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $data = [
            'centersCount' => PermissionHelper::can($user, 'App\Models\Admin\Center', 'view') ? Center::count() : null,
            'projectsCount' => PermissionHelper::can($user, 'App\Models\Admin\Project', 'view') ? Project::count() : null,
            'usersCount' => PermissionHelper::can($user, 'App\Models\User', 'view') ? User::count() : null,
            'groupsCount' => PermissionHelper::can($user, 'App\Models\Admin\Group', 'view') ? Group::count() : null,
        ];

        return view('admin.dashboard.index', $data);
    }
}
