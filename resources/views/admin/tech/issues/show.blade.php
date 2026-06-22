@extends('admin.layouts.master')

@section('title', $issue->title)

@section('content')
<div class="page-header">
    <h4>{{ $issue->title }}</h4>
    <p>
        <a href="{{ route('admin.tech.issues.index') }}" class="text-decoration-none">التذاكر الفنية</a>
        / #{{ $issue->id }}
    </p>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="form-card mb-4">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <h5>تفاصيل التذكرة</h5>
                <div class="d-flex gap-2">
                    <span class="badge fs-6
                        @switch($issue->status)
                            @case('open') bg-primary @break
                            @case('in_progress') bg-warning text-dark @break
                            @case('completed') bg-success @break
                            @case('blocked') bg-danger @break
                        @endswitch">
                        @switch($issue->status)
                            @case('open') مفتوحة @break
                            @case('in_progress') قيد التنفيذ @break
                            @case('completed') مكتملة @break
                            @case('blocked') مغلقة @break
                        @endswitch
                    </span>
                    <span class="badge fs-6
                        @switch($issue->priority)
                            @case('low') bg-secondary @break
                            @case('medium') bg-info @break
                            @case('high') bg-warning text-dark @break
                            @case('urgent') bg-danger @break
                        @endswitch">
                        @switch($issue->priority)
                            @case('low') أولوية منخفضة @break
                            @case('medium') أولوية متوسطة @break
                            @case('high') أولوية مرتفعة @break
                            @case('urgent') أولوية عاجلة @break
                        @endswitch
                    </span>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold">الوصف</label>
                <p class="mb-0">{{ nl2br(e($issue->description)) }}</p>
            </div>

            <div class="row g-3 mb-3 small text-muted">
                <div class="col-md-3">
                    <strong>المركز:</strong> {{ $issue->center?->name ?? '—' }}
                </div>
                <div class="col-md-3">
                    <strong>المشروع:</strong> {{ $issue->project?->name ?? '—' }}
                </div>
                <div class="col-md-3">
                    <strong>المبلغ:</strong> {{ $issue->reporter?->name ?? '—' }}
                </div>
                <div class="col-md-3">
                    <strong>المسند إلى:</strong> {{ $issue->assignee?->name ?? '—' }}
                </div>
                <div class="col-md-3">
                    <strong>تاريخ الإنشاء:</strong> {{ $issue->created_at->locale('ar')->translatedFormat('d M Y, h:i A') }}
                </div>
                <div class="col-md-3">
                    <strong>آخر تحديث:</strong> {{ $issue->updated_at->locale('ar')->translatedFormat('d M Y, h:i A') }}
                </div>
                @if ($issue->resolved_at)
                <div class="col-md-3">
                    <strong>تاريخ الحل:</strong> {{ $issue->resolved_at instanceof \Carbon\Carbon ? $issue->resolved_at->locale('ar')->translatedFormat('d M Y, h:i A') : $issue->resolved_at }}
                </div>
                @endif
            </div>

            @if ($issue->admin_response)
            <div class="mt-4 p-3 bg-light rounded">
                <label class="form-label fw-bold">رد المسؤول</label>
                <p class="mb-0">{{ nl2br(e($issue->admin_response)) }}</p>
            </div>
            @endif
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-card">
            <h5 class="mb-3">الرد على التذكرة</h5>
            <form method="POST" action="{{ route('admin.tech.issues.respond', $issue) }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label">الرد</label>
                    <textarea name="admin_response" rows="4"
                              class="form-control @error('admin_response') is-invalid @enderror"
                              placeholder="اكتب ردك هنا...">{{ old('admin_response') }}</textarea>
                    @error('admin_response') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">تحديث الحالة</label>
                    <select name="status" class="form-select @error('status') is-invalid @enderror">
                        <option value="open" {{ $issue->status === 'open' ? 'selected' : '' }}>مفتوحة</option>
                        <option value="in_progress" {{ $issue->status === 'in_progress' ? 'selected' : '' }}>قيد التنفيذ</option>
                        <option value="completed" {{ $issue->status === 'completed' ? 'selected' : '' }}>مكتملة</option>
                        <option value="blocked" {{ $issue->status === 'blocked' ? 'selected' : '' }}>مغلقة</option>
                    </select>
                    @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-send me-1"></i> إرسال الرد
                </button>
            </form>

            <hr>

            <a href="{{ route('admin.tech.issues.edit', $issue) }}" class="btn btn-outline-primary w-100">
                <i class="bi bi-pencil me-1"></i> تعديل التذكرة
            </a>
        </div>
    </div>
</div>
@endsection
