<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Student\Student;

class HomeController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // وجهات الهبوط المركزية حسب النوع (طالب/مستفيد/روادنا)
        if ($landing = $user->landingRoute()) {
            return redirect($landing);
        }

        $canProjectManager = \App\Helpers\PermissionHelper::can($user, 'page:admin.project-manager.dashboard', 'view');
        $canProjectsManager = \App\Helpers\PermissionHelper::can($user, 'page:admin.projects-manager.dashboard', 'view');
        $canProjectOfficer = \App\Helpers\PermissionHelper::can($user, 'page:admin.project-officer.dashboard', 'view');

        return view('admin.home.index', compact('canProjectManager', 'canProjectsManager', 'canProjectOfficer'));
    }
}
