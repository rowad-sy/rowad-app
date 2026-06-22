@extends('admin.layouts.master')

@section('title', 'المعدات التقنية')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4>المعدات التقنية</h4>
        <p>إدارة أجهزة ومعدات التقنية</p>
    </div>
    <a href="{{ route('admin.tech.equipment.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> إضافة معدة
    </a>
</div>

<div class="table-container">
    <div class="p-3 border-bottom">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small mb-1">بحث</label>
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="بحث بالاسم أو الرقم التسلسلي..." value="{{ $search }}">
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">المركز</label>
                <select name="center_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">الكل</option>
                    @foreach ($centers as $center)
                        <option value="{{ $center->id }}" {{ (int)($centerId ?? '') === $center->id ? 'selected' : '' }}>{{ $center->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">المشروع</label>
                <select name="project_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">الكل</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}" {{ (int)($projectId ?? '') === $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">النوع</label>
                <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all" {{ ($type ?? 'all') === 'all' ? 'selected' : '' }}>الكل</option>
                    @php
                        $types = \App\Models\Admin\Tech\TechEquipment::select('type')->distinct()->orderBy('type')->pluck('type');
                    @endphp
                    @foreach ($types as $t)
                        <option value="{{ $t }}" {{ ($type ?? '') === $t ? 'selected' : '' }}>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">الحالة</label>
                <select name="condition" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all" {{ ($condition ?? 'all') === 'all' ? 'selected' : '' }}>الكل</option>
                    <option value="a" {{ ($condition ?? '') === 'a' ? 'selected' : '' }}>ممتاز</option>
                    <option value="b" {{ ($condition ?? '') === 'b' ? 'selected' : '' }}>جيد</option>
                    <option value="c" {{ ($condition ?? '') === 'c' ? 'selected' : '' }}>متوسط</option>
                    <option value="d" {{ ($condition ?? '') === 'd' ? 'selected' : '' }}>سيئ</option>
                    <option value="e" {{ ($condition ?? '') === 'e' ? 'selected' : '' }}>تالف</option>
                </select>
            </div>
            <div class="col-md-1">
                <label class="form-label small mb-1">&nbsp;</label>
                <x-per-page-selector :perPage="$perPage ?? 10" />
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>الاسم</th>
                    <th>النوع</th>
                    <th>الرقم التسلسلي</th>
                    <th>الحالة</th>
                    <th>الغرفة</th>
                    <th>المركز</th>
                    <th>المشروع</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($equipment as $item)
                    <tr>
                        <td>{{ $item->id }}</td>
                        <td class="fw-medium">{{ $item->name }}</td>
                        <td>{{ $item->type }}</td>
                        <td><code>{{ $item->serial_number ?? '—' }}</code></td>
                        <td>
                            @switch($item->condition)
                                @case('a') <span class="badge bg-success">ممتاز</span> @break
                                @case('b') <span class="badge bg-primary">جيد</span> @break
                                @case('c') <span class="badge bg-warning text-dark">متوسط</span> @break
                                @case('d') <span class="badge bg-danger">سيئ</span> @break
                                @case('e') <span class="badge bg-dark">تالف</span> @break
                            @endswitch
                        </td>
                        <td>{{ $item->room ?? '—' }}</td>
                        <td>{{ $item->center?->name ?? '—' }}</td>
                        <td>{{ $item->project?->name ?? '—' }}</td>
                        <td>
                            <a href="{{ route('admin.tech.equipment.edit', $item) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="POST" action="{{ route('admin.tech.equipment.destroy', $item) }}" class="d-inline"
                                  onsubmit="return confirm('هل أنت متأكد من حذف هذه المعدة؟')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            لا توجد معدات
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="p-3 d-flex justify-content-between align-items-center">
        <div class="text-muted small">
            إجمالي: {{ $equipment->total() }} معدة
        </div>
        <div>
            {{ $equipment->links() }}
        </div>
    </div>
</div>
@endsection
