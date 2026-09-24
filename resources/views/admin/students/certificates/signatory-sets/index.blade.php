@extends('admin.layouts.master')

@section('title', 'مجموعات التوقيع')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4>مجموعات التوقيع</h4>
        <p>كل مجموعة تربط (مقرر + فترة + مركز) بثلاثة موقعين: المدرب ومدير المركز ومسؤول المشروع</p>
    </div>
    @canPermission('App\Models\Admin\Student\Certificate', 'create')
    <a href="{{ route('admin.students.certificates.signatory-sets.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> إضافة مجموعة
    </a>
    @endcanPermission
</div>

<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link" href="{{ route('admin.students.certificates.signers.index') }}">
            <i class="bi bi-person-sign me-1"></i> الموقعون
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link active" href="{{ route('admin.students.certificates.signatory-sets.index') }}">
            <i class="bi bi-card-checklist me-1"></i> مجموعات التوقيع
        </a>
    </li>
</ul>

<div class="table-container">
    <div class="p-3 border-bottom">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small mb-1">بحث</label>
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="بحث باسم المجموعة..." value="{{ $search }}">
                    <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">&nbsp;</label>
                <x-per-page-selector :perPage="$sets->perPage()" />
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>اسم المجموعة</th>
                    <th>المقرر</th>
                    <th>الفترة</th>
                    <th>المركز</th>
                    <th>المدرب</th>
                    <th>مدير المركز</th>
                    <th>مسؤول المشروع</th>
                    <th>الشهادات</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($sets as $set)
                    <tr>
                        <td>{{ $set->id }}</td>
                        <td class="fw-medium">{{ $set->name }}</td>
                        <td>{{ $set->course?->name_ar ?? 'عام' }}</td>
                        <td>{{ $set->period?->name_ar ?? 'أي فترة' }}</td>
                        <td>{{ $set->center?->name ?? 'أي مركز' }}</td>
                        <td>
                            {{ $set->instructorSigner?->name_ar ?? '—' }}
                            @if ($set->instructorSigner?->signature_path)
                                <i class="bi bi-check-circle text-success" title="له توقيع"></i>
                            @endif
                        </td>
                        <td>
                            {{ $set->centerManagerSigner?->name_ar ?? '—' }}
                            @if ($set->centerManagerSigner?->signature_path)
                                <i class="bi bi-check-circle text-success" title="له توقيع"></i>
                            @endif
                        </td>
                        <td>
                            {{ $set->projectManagerSigner?->name_ar ?? '—' }}
                            @if ($set->projectManagerSigner?->signature_path)
                                <i class="bi bi-check-circle text-success" title="له توقيع"></i>
                            @endif
                        </td>
                        <td><span class="badge bg-secondary">{{ $set->certificates_count }}</span></td>
                        <td>
                            @canPermission('App\Models\Admin\Student\Certificate', 'create')
                            <a href="{{ route('admin.students.certificates.signatory-sets.edit', $set) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            @endcanPermission
                            @canPermission('App\Models\Admin\Student\Certificate', 'delete')
                            <form method="POST" action="{{ route('admin.students.certificates.signatory-sets.destroy', $set) }}" class="d-inline"
                                  onsubmit="return confirm('حذف هذه المجموعة؟ الشهادات المرتبطة بها ستفقد أسماء الموقعين وتواقيعها.')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                            @endcanPermission
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center py-4 text-muted">
                            <i class="bi bi-card-checklist fs-3 d-block mb-2"></i>
                            لا توجد مجموعات توقيعات
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="p-3 d-flex justify-content-between align-items-center">
        <div class="text-muted small">إجمالي: {{ $sets->total() }} مجموعة</div>
        <div>{{ $sets->links() }}</div>
    </div>
</div>
@endsection
