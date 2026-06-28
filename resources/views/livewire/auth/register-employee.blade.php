<?php

use App\Models\Admin\Center;
use App\Models\Admin\Hr\Employee;
use App\Models\Admin\Project;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth-bootstrap', ['title' => 'تسجيل موظف جديد', 'description' => 'أدخل بياناتك لإنشاء حساب موظف في النظام'])] class extends Component {
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $center_id = '';
    public string $project_id = '';

    public function with(): array
    {
        return [
            'centers' => Center::orderBy('name')->get(),
            'projects' => Project::orderBy('name')->get(),
        ];
    }

    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
            'center_id' => ['nullable', 'exists:centers,id'],
            'project_id' => ['nullable', 'exists:projects,id'],
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['type'] = 'employee';

        event(new Registered(($user = User::create($validated))));

        Auth::login($user);

        $employee = Employee::where('user_id', $user->id)->first();
        $redirectRoute = $employee ? route('admin.hr.employees.show', $employee, absolute: false) : route('admin.dashboard', absolute: false);
        $this->redirect($redirectRoute, navigate: true);
    }
}; ?>

<div>
    <form wire:submit="register">
        <div class="mb-3">
            <label class="form-label">الاسم الكامل</label>
            <input type="text" wire:model="name" class="form-control @error('name') is-invalid @enderror"
                   placeholder="الاسم الأول واللقب" required autofocus autocomplete="name">
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">البريد الإلكتروني</label>
            <input type="email" wire:model="email" class="form-control @error('email') is-invalid @enderror"
                   placeholder="example@example.com" required autocomplete="email">
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label">المركز (اختياري)</label>
                <select wire:model="center_id" class="form-select">
                    <option value="">اختر المركز</option>
                    @foreach ($centers as $center)
                        <option value="{{ $center->id }}">{{ $center->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">المشروع (اختياري)</label>
                <select wire:model="project_id" class="form-select">
                    <option value="">اختر المشروع</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}">{{ $project->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">كلمة المرور</label>
            <input type="password" wire:model="password" class="form-control @error('password') is-invalid @enderror"
                   placeholder="••••••••" required autocomplete="new-password">
            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-4">
            <label class="form-label">تأكيد كلمة المرور</label>
            <input type="password" wire:model="password_confirmation" class="form-control @error('password_confirmation') is-invalid @enderror"
                   placeholder="••••••••" required autocomplete="new-password">
            @error('password_confirmation') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <button type="submit" class="btn btn-auth" wire:loading.attr="disabled">
            <span wire:loading.remove>إنشاء الحساب</span>
            <span wire:loading><span class="spinner-border spinner-border-sm me-1"></span> جاري الإنشاء...</span>
        </button>
    </form>

    <div class="auth-divider"><span>أو</span></div>
    <div class="d-flex justify-content-between auth-footer" style="margin-top:0">
        <a href="{{ route('login') }}">لدي حساب بالفعل</a>
        <a href="{{ route('auth.choose') }}">العودة</a>
    </div>
</div>
