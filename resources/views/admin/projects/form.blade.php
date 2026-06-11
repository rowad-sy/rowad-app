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

                <div class="mb-3">
                    <label class="form-label">المراكز النشطة</label>
                    <div class="row g-2 mt-1">
                        @foreach ($centers as $center)
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input type="checkbox" name="centers[]" value="{{ $center->id }}"
                                           class="form-check-input"
                                           id="center_{{ $center->id }}"
                                           {{ in_array($center->id, old('centers', $project->centers->pluck('id')->toArray() ?? [])) ? 'checked' : '' }}>
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
