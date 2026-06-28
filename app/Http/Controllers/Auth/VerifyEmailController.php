<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Admin\Hr\Employee;
use App\Models\Admin\Student\Student;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        $user = $request->user();
        $redirectRoute = $this->getRedirectRoute($user);

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended($redirectRoute.'?verified=1');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return redirect()->intended($redirectRoute.'?verified=1');
    }

    private function getRedirectRoute($user): string
    {
        if ($user->type === 'student') {
            $student = Student::where('user_id', $user->id)->first();
            return $student
                ? route('admin.students.show', $student, absolute: false)
                : route('admin.home', absolute: false);
        } elseif ($user->type === 'beneficiary') {
            return route('admin.beneficiary.dashboard', absolute: false);
        }

        $employee = Employee::where('user_id', $user->id)->first();
        return $employee
            ? route('admin.hr.employees.show', $employee, absolute: false)
            : route('admin.dashboard', absolute: false);
    }
}

