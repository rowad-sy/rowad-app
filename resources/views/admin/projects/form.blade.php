@extends('admin.layouts.master')

@section('title', isset($project) ? 'تعديل مشروع' : 'إضافة مشروع')

@section('content')
<div class="page-header">
    <h4>{{ isset($project) ? 'تعديل المشروع' : 'إضافة مشروع' }}</h4>
    <p>
        <a href="{{ route('admin.projects.index') }}" class="text-decoration-none">المشاريع</a>
        / {{ isset($project) ? $project->name : 'جديد' }}
    </p>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="form-card">
            <form method="POST"
                  action="{{ isset($project) ? route('admin.projects.update', $project) : route('admin.projects.store') }}">
                @csrf
                @if (isset($project))
                    @method('PUT')
                @endif

                <div class="mb-3">
                    <label class="form-label">اسم المشروع <span class="text-danger">*</span></label>
                    <input type="text" name="name"
                           class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $project->name ?? '') }}" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">الوصف</label>
                    <textarea name="description" rows="4"
                              class="form-control @error('description') is-invalid @enderror">{{ old('description', $project->description ?? '') }}</textarea>
                    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">كود المشروع</label>
                        <input type="text" name="code"
                               class="form-control @error('code') is-invalid @enderror"
                               value="{{ old('code', $project->code ?? '') }}">
                        @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">حالة المشروع</label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror">
                            @foreach (\App\Models\Admin\Project::STATUSES as $key => $label)
                                <option value="{{ $key }}" @selected(old('status', $project->status ?? 'active') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">المسار</label>
                    <select name="path_id" class="form-select @error('path_id') is-invalid @enderror">
                        <option value="">— بدون مسار —</option>
                        @foreach ($paths as $path)
                            <option value="{{ $path->id }}" @selected((int) old('path_id', $project->path_id ?? 0) === $path->id)>{{ $path->name }}</option>
                        @endforeach
                    </select>
                    @error('path_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="form-text">يمكن إدارة المسارات من صفحة المسارات في قسم إدارة المشاريع.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">المراكز النشطة</label>
                    <div class="row g-2 mt-1">
                        @foreach ($centers as $center)
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input type="checkbox" name="centers[]" value="{{ $center->id }}"
                                           class="form-check-input"
                                           id="center_{{ $center->id }}"
                                            {{ in_array($center->id, old('centers', isset($project) ? $project->centers->pluck('id')->toArray() : [])) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="center_{{ $center->id }}">
                                        {{ $center->name }}
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> حفظ
                    </button>
                    <a href="{{ route('admin.projects.index') }}" class="btn btn-outline-secondary">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
