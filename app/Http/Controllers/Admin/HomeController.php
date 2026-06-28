<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Student\Student;

class HomeController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if ($user->type === 'student') {
            $student = Student::where('user_id', $user->id)->first();
            return redirect($student
                ? route('admin.students.show', $student, absolute: false)
                : route('admin.profile', absolute: false));
        }

        if ($user->type === 'beneficiary') {
            return redirect(route('admin.beneficiary.dashboard', absolute: false));
        }

        return view('admin.home.index');
    }
}
