<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\User,view')->only(['index']);
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $users = User::withCount('groups')
            ->when($search, function ($q, $search) {
                return $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })->orderBy('name')->paginate(10);

        return view('admin.users.index', compact('users', 'search'));
    }

    public function toggleStatus(User $user)
    {
        $user->update(['is_active' => !$user->is_active]);

        return redirect()->route('admin.users.index')
            ->with('success', 'تم تغيير حالة المستخدم بنجاح');
    }
}
