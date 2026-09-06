@extends('admin.layouts.master')

@section('title', $level->name_ar)

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4>{{ $level->name_ar }}</h4>
        <p>
            <a href="{{ route('admin.students.levels.index') }}" class="text-decoration-none">المستويات والصفوف</a>
            / {{ $level->name_ar }}
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.students.levels.edit', $level) }}" class="btn btn-primary"><i class="bi bi-pencil me-1"></i> تعديل</a>
        <a href="{{ route('admin.students.levels.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-right me-1"></i> رجوع</a>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <small class="text-muted d-block">المشروع</small>
                <strong>{{ $level->project?->name ?? '—' }}</strong>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">النوع</small>
                <span class="badge bg-secondary">{{ $level->type }}</span>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">الترتيب</small>
                <strong>{{ $level->sort_order }}</strong>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">عدد المواد</small>
                <strong>{{ $level->subjects->count() }} مادة</strong>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h6 class="mb-0">مواد المستوى والمدرّسون</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>المادة</th>
                        <th>المدرّسون</th>
                        <th>المدرّس الرئيسي</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($level->subjects as $index => $subject)
                        @php
                            $instructors = $level->subjectInstructors->where('subject_id', $subject->id);
                        @endphp
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td class="fw-medium">{{ $subject->name_ar }}</td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    @forelse ($instructors as $lsi)
                                        <span class="badge bg-secondary-subtle text-dark">{{ $lsi->instructor?->first_name_ar }} {{ $lsi->instructor?->last_name_ar }}</span>
                                    @empty
                                        <span class="text-muted small">—</span>
                                    @endforelse
                                </div>
                            </td>
                            <td>
                                @php $main = $instructors->firstWhere('is_main', true); @endphp
                                {{ $main ? ($main->instructor?->first_name_ar . ' ' . $main->instructor?->last_name_ar) : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">لا توجد مواد لهذا المستوى</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
