@extends('admin.layouts.master')

@section('title', 'موقعو الشهادات')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4>موقعو الشهادات</h4>
        <p>مكتبة الأسماء والتواقيع المستخدمة في الشهادات</p>
    </div>
    @canPermission('App\Models\Admin\Student\Certificate', 'create')
    <a href="{{ route('admin.students.certificates.signers.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> إضافة موقع
    </a>
    @endcanPermission
</div>

<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link active" href="{{ route('admin.students.certificates.signers.index') }}">
            <i class="bi bi-person-sign me-1"></i> الموقعون
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="{{ route('admin.students.certificates.signatory-sets.index') }}">
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
                    <input type="text" name="search" class="form-control" placeholder="بحث بالاسم..." value="{{ $search }}">
                    <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">الدور</label>
                <select name="role" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">الكل</option>
                    @foreach (\App\Models\Admin\Student\CertificateSigner::ROLES as $key => $label)
                        <option value="{{ $key }}" {{ $role === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">&nbsp;</label>
                <x-per-page-selector :perPage="$signers->perPage()" />
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>الاسم</th>
                    <th>الدور</th>
                    <th>التوقيع</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($signers as $signer)
                    <tr>
                        <td>{{ $signer->id }}</td>
                        <td class="fw-medium">{{ $signer->name_ar }}</td>
                        <td><span class="badge bg-info text-dark">{{ $signer->roleLabel() }}</span></td>
                        <td>
                            @if ($signer->signature_path)
                                <img src="{{ asset('storage/' . $signer->signature_path) }}" style="max-height:40px;max-width:120px;object-fit:contain;background:#f8fafc;border:1px solid #dee2e6;border-radius:4px;padding:2px;" alt="توقيع {{ $signer->name_ar }}">
                            @else
                                <span class="text-muted small">بدون توقيع</span>
                            @endif
                        </td>
                        <td>
                            @canPermission('App\Models\Admin\Student\Certificate', 'create')
                            <a href="{{ route('admin.students.certificates.signers.edit', $signer) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            @endcanPermission
                            @canPermission('App\Models\Admin\Student\Certificate', 'delete')
                            <form method="POST" action="{{ route('admin.students.certificates.signers.destroy', $signer) }}" class="d-inline"
                                  onsubmit="return confirm('هل أنت متأكد من حذف هذا الموقع؟')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                            @endcanPermission
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">
                            <i class="bi bi-person-x fs-3 d-block mb-2"></i>
                            لا يوجد موقعون. أضف مدرباً ومديري مراكز ومشاريع مع صور تواقيعهم.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="p-3 d-flex justify-content-between align-items-center">
        <div class="text-muted small">إجمالي: {{ $signers->total() }} موقع</div>
        <div>{{ $signers->links() }}</div>
    </div>
</div>
@endsection
