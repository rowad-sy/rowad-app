<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
        return view('admin.profile.index', compact('user'));
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
