@extends('admin.layouts.master')

@section('title', isset($permission) ? 'تعديل صلاحية' : 'إضافة صلاحية')

@push('styles')
<style>
    .model-radio-group {
        display: flex;
        gap: 1rem;
        margin-bottom: 1rem;
    }
    .perm-checkboxes label {
        margin-left: 1rem;
    }
</style>
@endpush

@section('content')
<div class="page-header">
    <h4>{{ isset($permission) ? 'تعديل الصلاحية' : 'إضافة صلاحية' }}</h4>
    <p>
        <a href="{{ route('admin.permissions.index') }}" class="text-decoration-none">الصلاحيات</a>
        / {{ isset($permission) ? 'تعديل' : 'جديد' }}
    </p>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="form-card">
            <form method="POST"
                  action="{{ isset($permission) ? route('admin.permissions.update', $permission) : route('admin.permissions.store') }}">
                @csrf
                @if (isset($permission))
                    @method('PUT')
                @endif

                {{-- تعيين الصلاحية لمستخدم أم مجموعة --}}
                <div class="mb-3">
                    <label class="form-label">تعيين إلى</label>
                    <div class="model-radio-group">
                        <div class="form-check">
                            <input type="radio" name="assign_to" value="user" class="form-check-input"
                                   id="assign_user"
                                   {{ old('assign_to', $permission->user_id ?? 'user') === 'user' ? 'checked' : '' }}
                                   onchange="toggleAssignType()">
                            <label class="form-check-label" for="assign_user">مستخدم</label>
                        </div>
                        <div class="form-check">
                            <input type="radio" name="assign_to" value="group" class="form-check-input"
                                   id="assign_group"
                                   {{ old('assign_to', $permission->group_id ?? '') === 'group' ? 'checked' : '' }}
                                   onchange="toggleAssignType()">
                            <label class="form-check-label" for="assign_group">مجموعة</label>
                        </div>
                    </div>
                </div>

                <div class="mb-3" id="user_select_div">
                    <label class="form-label">المستخدم</label>
                    <select name="user_id" class="form-select @error('user_id') is-invalid @enderror">
                        <option value="">اختر مستخدم</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}"
                                {{ old('user_id', $permission->user_id ?? '') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }} ({{ $user->email }})
                            </option>
                        @endforeach
                    </select>
                    @error('user_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3" id="group_select_div" style="display:none;">
                    <label class="form-label">المجموعة</label>
                    <select name="group_id" class="form-select @error('group_id') is-invalid @enderror">
                        <option value="">اختر مجموعة</option>
                        @foreach ($groups as $group)
                            <option value="{{ $group->id }}"
                                {{ old('group_id', $permission->group_id ?? '') == $group->id ? 'selected' : '' }}>
                                {{ $group->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('group_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- الموديلات --}}
                <div class="mb-3">
                    <label class="form-label">الموديلات <span class="text-danger">*</span></label>
                    <div class="row g-2 mt-1">
                        @foreach ($availableModels as $value => $label)
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input type="checkbox" name="model_names[]" value="{{ $value }}"
                                           class="form-check-input @error('model_names') is-invalid @enderror"
                                           id="model_{{ Str::slug($label) }}"
                                           {{ in_array($value, old('model_names', isset($permission) ? ($permission->model_names ?? []) : [])) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="model_{{ Str::slug($label) }}">
                                        {{ $label }}
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @error('model_names') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    @error('model_names.*') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    <small class="text-muted">اختر موديل واحد أو أكثر لإنشاء صلاحية واحدة تشمل جميع الموديلات المحددة</small>
                </div>

                {{-- النطاق --}}
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">المركز</label>
                        <select name="center_id" class="form-select @error('center_id') is-invalid @enderror">
                            <option value="">جميع المراكز</option>
                            @foreach ($centers as $center)
                                <option value="{{ $center->id }}"
                                    {{ old('center_id', $permission->center_id ?? '') == $center->id ? 'selected' : '' }}>
                                    {{ $center->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('center_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">المشروع</label>
                        <select name="project_id" class="form-select @error('project_id') is-invalid @enderror">
                            <option value="">جميع المشاريع</option>
                            @foreach ($projects as $project)
                                <option value="{{ $project->id }}"
                                    {{ old('project_id', $permission->project_id ?? '') == $project->id ? 'selected' : '' }}>
                                    {{ $project->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('project_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- أنواع الصلاحيات --}}
                <div class="mb-3">
                    <label class="form-label">الصلاحيات</label>
                    <div class="perm-checkboxes d-flex flex-wrap gap-3 mt-1">
                        <div class="form-check">
                            <input type="checkbox" name="can_view" value="1" class="form-check-input" id="can_view"
                                {{ old('can_view', $permission->can_view ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label" for="can_view">عرض</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" name="can_create" value="1" class="form-check-input" id="can_create"
                                {{ old('can_create', $permission->can_create ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label" for="can_create">إضافة</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" name="can_edit" value="1" class="form-check-input" id="can_edit"
                                {{ old('can_edit', $permission->can_edit ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label" for="can_edit">تعديل</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" name="can_delete" value="1" class="form-check-input" id="can_delete"
                                {{ old('can_delete', $permission->can_delete ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label" for="can_delete">حذف</label>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> حفظ
                    </button>
                    <a href="{{ route('admin.permissions.index') }}" class="btn btn-outline-secondary">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function toggleAssignType() {
    var userRadio = document.getElementById('assign_user');
    document.getElementById('user_select_div').style.display = userRadio.checked ? 'block' : 'none';
    document.getElementById('group_select_div').style.display = userRadio.checked ? 'none' : 'block';
}
document.addEventListener('DOMContentLoaded', toggleAssignType);
</script>
@endpush
