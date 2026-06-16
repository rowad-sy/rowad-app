@extends('admin.layouts.master')

@section('title', isset($student) ? 'تعديل طالب' : 'إضافة طالب')

@section('content')
<div class="page-header">
    <h4>{{ isset($student) ? 'تعديل بيانات الطالب' : 'إضافة طالب جديد' }}</h4>
    <p>
        <a href="{{ route('admin.students.index') }}" class="text-decoration-none">الطلاب</a>
        / {{ isset($student) ? $student->first_name_ar . ' ' . $student->last_name_ar : 'جديد' }}
    </p>
</div>

<form method="POST" action="{{ isset($student) ? route('admin.students.update', $student) : route('admin.students.store') }}">
    @csrf
    @if (isset($student))
        @method('PUT')
    @endif

    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">المستخدم (اختياري)</label>
            <select name="user_id" class="form-select @error('user_id') is-invalid @enderror">
                <option value="">— بدون مستخدم —</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" {{ old('user_id', $student->user_id ?? '') == $user->id ? 'selected' : '' }}>{{ $user->name }} ({{ $user->email }})</option>
                @endforeach
            </select>
            @error('user_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-2">
            <label class="form-label">كود الطالب <span class="text-danger">*</span></label>
            <input type="text" name="student_code" class="form-control @error('student_code') is-invalid @enderror"
                   value="{{ old('student_code', $student->student_code ?? '') }}" required>
            @error('student_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-2">
            <label class="form-label">الحالة</label>
            <select name="status" class="form-select">
                <option value="active" {{ old('status', $student->status ?? 'active') === 'active' ? 'selected' : '' }}>نشط</option>
                <option value="inactive" {{ old('status', $student->status ?? '') === 'inactive' ? 'selected' : '' }}>غير نشط</option>
                <option value="graduated" {{ old('status', $student->status ?? '') === 'graduated' ? 'selected' : '' }}>متخرج</option>
                <option value="suspended" {{ old('status', $student->status ?? '') === 'suspended' ? 'selected' : '' }}>موقوف</option>
            </select>
        </div>

        <div class="col-md-4">
            <label class="form-label">تاريخ التسجيل</label>
            <input type="date" name="enrollment_date" class="form-control @error('enrollment_date') is-invalid @enderror"
                   value="{{ old('enrollment_date', $student->enrollment_date ?? '') }}">
            @error('enrollment_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-6">
            <label class="form-label">الاسم AR <span class="text-danger">*</span></label>
            <div class="row g-2">
                <div class="col-6">
                    <input type="text" name="first_name_ar" class="form-control @error('first_name_ar') is-invalid @enderror"
                           value="{{ old('first_name_ar', $student->first_name_ar ?? '') }}" placeholder="الاسم" required>
                </div>
                <div class="col-6">
                    <input type="text" name="last_name_ar" class="form-control @error('last_name_ar') is-invalid @enderror"
                           value="{{ old('last_name_ar', $student->last_name_ar ?? '') }}" placeholder="اللقب" required>
                </div>
            </div>
            @error('first_name_ar') <div class="invalid-feedback">{{ $message }}</div> @enderror
            @error('last_name_ar') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-6">
            <label class="form-label">الاسم EN</label>
            <div class="row g-2">
                <div class="col-6">
                    <input type="text" name="first_name_en" class="form-control @error('first_name_en') is-invalid @enderror"
                           value="{{ old('first_name_en', $student->first_name_en ?? '') }}" placeholder="First Name" dir="ltr">
                </div>
                <div class="col-6">
                    <input type="text" name="last_name_en" class="form-control @error('last_name_en') is-invalid @enderror"
                           value="{{ old('last_name_en', $student->last_name_en ?? '') }}" placeholder="Last Name" dir="ltr">
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <label class="form-label">اسم الأب</label>
            <input type="text" name="father_name" class="form-control"
                   value="{{ old('father_name', $student->father_name ?? '') }}">
        </div>

        <div class="col-md-3">
            <label class="form-label">اسم الأم</label>
            <input type="text" name="mother_name" class="form-control"
                   value="{{ old('mother_name', $student->mother_name ?? '') }}">
        </div>

        <div class="col-md-2">
            <label class="form-label">الجنس <span class="text-danger">*</span></label>
            <select name="gender" class="form-select @error('gender') is-invalid @enderror" required>
                <option value="male" {{ old('gender', $student->gender ?? '') === 'male' ? 'selected' : '' }}>ذكر</option>
                <option value="female" {{ old('gender', $student->gender ?? '') === 'female' ? 'selected' : '' }}>أنثى</option>
            </select>
            @error('gender') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-2">
            <label class="form-label">تاريخ الميلاد</label>
            <input type="date" name="birth_date" class="form-control @error('birth_date') is-invalid @enderror"
                   value="{{ old('birth_date', $student->birth_date ?? '') }}">
            @error('birth_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-2">
            <label class="form-label">مكان الميلاد</label>
            <input type="text" name="birth_place" class="form-control"
                   value="{{ old('birth_place', $student->birth_place ?? '') }}">
        </div>

        <div class="col-md-2">
            <label class="form-label">الجنسية</label>
            <input type="text" name="nationality" class="form-control"
                   value="{{ old('nationality', $student->nationality ?? '') }}">
        </div>

        <div class="col-md-3">
            <label class="form-label">الهاتف</label>
            <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                   value="{{ old('phone', $student->phone ?? '') }}" dir="ltr">
            @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-3">
            <label class="form-label">البريد الإلكتروني</label>
            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                   value="{{ old('email', $student->email ?? '') }}" dir="ltr">
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-4">
            <label class="form-label">المركز</label>
            <select name="center_id" class="form-select">
                <option value="">اختر مركز</option>
                @foreach ($centers as $center)
                    <option value="{{ $center->id }}" {{ old('center_id', $student->center_id ?? '') == $center->id ? 'selected' : '' }}>{{ $center->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-4">
            <label class="form-label">المشروع</label>
            <select name="project_id" class="form-select">
                <option value="">اختر مشروع</option>
                @foreach ($projects as $project)
                    <option value="{{ $project->id }}" {{ old('project_id', $student->project_id ?? '') == $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label">العنوان</label>
            <textarea name="address" rows="2" class="form-control">{{ old('address', $student->address ?? '') }}</textarea>
        </div>

        <div class="col-md-6">
            <label class="form-label">ملاحظات</label>
            <textarea name="notes" rows="2" class="form-control">{{ old('notes', $student->notes ?? '') }}</textarea>
        </div>
    </div>

    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="bi bi-check-lg me-1"></i> حفظ
        </button>
        <a href="{{ route('admin.students.index') }}" class="btn btn-outline-secondary btn-lg">إلغاء</a>
    </div>
</form>
@endsection
