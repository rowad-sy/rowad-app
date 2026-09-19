@extends('admin.layouts.master')

@section('title', isset($path) ? 'تعديل مسار' : 'إضافة مسار')

@section('content')
<div class="page-header">
    <h4>{{ isset($path) ? 'تعديل المسار' : 'إضافة مسار جديد' }}</h4>
    <p>
        <a href="{{ route('admin.paths.index') }}" class="text-decoration-none">المسارات</a>
        / {{ isset($path) ? $path->name : 'جديد' }}
    </p>
</div>

<form method="POST" action="{{ isset($path) ? route('admin.paths.update', $path) : route('admin.paths.store') }}">
    @csrf
    @if (isset($path)) @method('PUT') @endif

    <div class="row">
        <div class="col-lg-5">
            <div class="form-card mb-3">
                <div class="mb-3">
                    <label class="form-label">اسم المسار <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $path->name ?? '') }}" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">كود المسار</label>
                    <input type="text" name="code" class="form-control @error('code') is-invalid @enderror"
                           value="{{ old('code', $path->code ?? '') }}">
                    @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">الوصف</label>
                    <textarea name="description" rows="4" class="form-control @error('description') is-invalid @enderror">{{ old('description', $path->description ?? '') }}</textarea>
                    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="form-card">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <label class="form-label mb-0 fw-bold">اختر المشاريع التابعة للمسار</label>
                    <span class="badge text-bg-light border">المحدد: <span id="selectedCount">0</span></span>
                </div>
                <input type="text" id="projectSearch" class="form-control form-control-sm mb-2" placeholder="بحث في المشاريع...">
                <div class="border rounded p-2" style="max-height: 360px; overflow-y: auto;">
                    @forelse ($projects as $project)
                        @php
                            $checked = in_array($project->id, old('projects', isset($path) ? $path->projects->pluck('id')->toArray() : []));
                        @endphp
                        <div class="form-check project-item" data-name="{{ $project->name }}">
                            <input class="form-check-input project-check" type="checkbox" name="projects[]"
                                   value="{{ $project->id }}" id="project_{{ $project->id }}" {{ $checked ? 'checked' : '' }}>
                            <label class="form-check-label" for="project_{{ $project->id }}">
                                {{ $project->name }}
                                <span class="status-pill {{ $project->statusBadgeClass() }} ms-1">{{ $project->statusLabel() }}</span>
                            </label>
                        </div>
                    @empty
                        <div class="text-muted small text-center py-3">لا توجد مشاريع — أضف المشاريع أولاً.</div>
                    @endforelse
                </div>
                <div class="text-muted small mt-2">
                    <i class="bi bi-info-circle me-1"></i> المشروع ينتمي لمسار واحد؛ اختياره هنا يفصله عن أي مسار آخر.
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mt-3">
        <button type="submit" class="btn btn-brand"><i class="bi bi-check-lg me-1"></i> حفظ</button>
        <a href="{{ route('admin.paths.index') }}" class="btn btn-outline-secondary">إلغاء</a>
    </div>
</form>
@endsection

@push('scripts')
<script>
    function updateCount() {
        document.getElementById('selectedCount').textContent = document.querySelectorAll('.project-check:checked').length;
    }
    document.addEventListener('DOMContentLoaded', function () {
        updateCount();
        document.querySelectorAll('.project-check').forEach(function (cb) {
            cb.addEventListener('change', updateCount);
        });
        document.getElementById('projectSearch').addEventListener('input', function (e) {
            var q = e.target.value.trim().toLowerCase();
            document.querySelectorAll('.project-item').forEach(function (item) {
                item.style.display = item.dataset.name.toLowerCase().includes(q) ? '' : 'none';
            });
        });
    });
</script>
@endpush
