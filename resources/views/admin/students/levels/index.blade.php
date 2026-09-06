@extends('admin.layouts.master')

@section('title', 'المستويات والصفوف')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4>المستويات والصفوف</h4>
        <p>المستويات التعليمية والصفوف (الطفولة، الصفوف، مستويات التدريب) ومواد كل مستوى</p>
    </div>
    <div>
        <a href="{{ route('admin.students.levels.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> إضافة مستوى/صف
        </a>
    </div>
</div>

<div class="table-container">
    <div class="p-3 border-bottom">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small mb-1">بحث</label>
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="بحث بالاسم..." value="{{ $search }}">
                    <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">المشروع</label>
                <select name="project_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">الكل</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}" {{ (int)($projectId ?? '') === $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>

    @forelse ($levels as $projectId => $projectLevels)
        @php $project = $projectLevels->first()->project; @endphp
        <div class="p-3">
            <h5 class="mb-3"><i class="bi bi-diagram-3 me-1"></i> {{ $project->name }}</h5>
            <div class="row g-3">
                @foreach ($projectLevels as $level)
                    <div class="col-md-4">
                        <a href="{{ route('admin.students.levels.show', $level) }}" class="text-decoration-none">
                            <div class="card h-100 shadow-sm" style="cursor:pointer;">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1">{{ $level->name_ar }}</h6>
                                            <span class="badge bg-light text-dark">{{ $level->type }}</span>
                                        </div>
                                        <span class="badge bg-primary">{{ $level->subjects->count() }} مواد</span>
                                    </div>
                                    <div class="mt-3">
                                        <span class="text-muted small">المواد:</span>
                                        <div class="d-flex flex-wrap gap-1 mt-1">
                                            @foreach ($level->subjects->take(4) as $subject)
                                                <span class="badge bg-secondary-subtle text-dark">{{ $subject->name_ar }}</span>
                                            @endforeach
                                            @if ($level->subjects->count() > 4)
                                                <span class="badge bg-light text-muted">+{{ $level->subjects->count() - 4 }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <div class="p-5 text-center text-muted">
            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
            لا توجد مستويات
        </div>
    @endforelse
</div>
@endsection
