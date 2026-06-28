<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Hr\Employee;
use App\Models\Admin\Student\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->is_active) {
                abort(403, 'حسابك غير نشط');
            }
            return $next($request);
        });
    }

    public function index()
    {
        $user = auth()->user();

        // Pass user-specific data based on type
        $employee = null;
        $student = null;

        if ($user->type === 'employee' || $user->type === 'super-admin') {
            $employee = Employee::where('user_id', $user->id)->with(['center', 'department', 'project'])->first();
        } elseif ($user->type === 'student') {
            $student = Student::where('user_id', $user->id)->first();
        }

        return view('admin.profile.index', compact('user', 'employee', 'student'));
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $user->update($validated);

        return redirect()->route('admin.profile')
            ->with('success', 'تم تحديث الاسم بنجاح');
    }

    public function password(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'current_password' => 'required|current_password',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('admin.profile')
            ->with('success', 'تم تغيير كلمة المرور بنجاح');
    }
}
