@extends('admin.layouts.master')

@section('title', isset($issue) ? 'تعديل تذكرة' : 'إضافة تذكرة')

@section('content')
<x-page-header :title="isset($issue) ? 'تعديل التذكرة' : 'إضافة تذكرة'"
               :breadcrumb="[['label' => 'التذاكر الفنية', 'url' => route('admin.tech.issues.index')], ['label' => isset($issue) ? $issue->title : 'جديد']]" />

<div class="row">
    <div class="col-md-8">
        <div class="form-card">
            <form method="POST"
                  action="{{ isset($issue) ? route('admin.tech.issues.update', $issue) : route('admin.tech.issues.store') }}">
                @csrf
                @if (isset($issue))
                    @method('PUT')
                @endif

                <div class="mb-3">
                    <label class="form-label">العنوان <span class="text-danger">*</span></label>
                    <input type="text" name="title"
                           class="form-control @error('title') is-invalid @enderror"
                           value="{{ old('title', $issue->title ?? '') }}" required>
                    @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">الوصف <span class="text-danger">*</span></label>
                    <textarea name="description" rows="5"
                              class="form-control @error('description') is-invalid @enderror"
                              required>{{ old('description', $issue->description ?? '') }}</textarea>
                    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">المركز</label>
                        <select name="center_id" class="form-select @error('center_id') is-invalid @enderror">
                            <option value="">اختر المركز</option>
                            @foreach ($centers as $center)
                                <option value="{{ $center->id }}" {{ (old('center_id', $issue->center_id ?? $defaultCenterId ?? '') == $center->id) ? 'selected' : '' }}>{{ $center->name }}</option>
                            @endforeach
                        </select>
                        @error('center_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">المشروع</label>
                        <select name="project_id" class="form-select @error('project_id') is-invalid @enderror">
                            <option value="">اختر المشروع</option>
                            @foreach ($projects as $project)
                                <option value="{{ $project->id }}" {{ (old('project_id', $issue->project_id ?? $defaultProjectId ?? '') == $project->id) ? 'selected' : '' }}>{{ $project->name }}</option>
                            @endforeach
                        </select>
                        @error('project_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">الأولوية <span class="text-danger">*</span></label>
                        <select name="priority" class="form-select @error('priority') is-invalid @enderror" required>
                            <option value="low" {{ (old('priority', $issue->priority ?? '') == 'low') ? 'selected' : '' }}>منخفضة</option>
                            <option value="medium" {{ (old('priority', $issue->priority ?? '') == 'medium') ? 'selected' : '' }}>متوسطة</option>
                            <option value="high" {{ (old('priority', $issue->priority ?? '') == 'high') ? 'selected' : '' }}>مرتفعة</option>
                            <option value="urgent" {{ (old('priority', $issue->priority ?? '') == 'urgent') ? 'selected' : '' }}>عاجلة</option>
                        </select>
                        @error('priority') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">إسناد إلى</label>
                        <select name="assigned_to" class="form-select @error('assigned_to') is-invalid @enderror">
                            <option value="">لم يسند</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" {{ (old('assigned_to', $issue->assigned_to ?? '') == $user->id) ? 'selected' : '' }}>{{ $user->name }}</option>
                            @endforeach
                        </select>
                        @error('assigned_to') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                @if (isset($issue))
                <div class="mb-3">
                    <label class="form-label">الحالة</label>
                    <select name="status" class="form-select @error('status') is-invalid @enderror">
                        <option value="open" {{ $issue->status === 'open' ? 'selected' : '' }}>مفتوحة</option>
                        <option value="in_progress" {{ $issue->status === 'in_progress' ? 'selected' : '' }}>قيد التنفيذ</option>
                        <option value="completed" {{ $issue->status === 'completed' ? 'selected' : '' }}>مكتملة</option>
                        <option value="blocked" {{ $issue->status === 'blocked' ? 'selected' : '' }}>مغلقة</option>
                    </select>
                </div>
                @endif

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> حفظ
                    </button>
                    <a href="{{ route('admin.tech.issues.index') }}" class="btn btn-outline-secondary">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
