@extends('admin.layouts.master')

@section('title', 'موقعو الشهادات')

@section('content')
<x-page-header :title="'موقعو الشهادات'" :description="'مكتبة الأسماء والتواقيع المستخدمة في الشهادات'"
               :breadcrumb="[['label' => 'الطلاب'], ['label' => 'موقعو الشهادات']]">
    @canPermission('App\Models\Admin\Student\Certificate', 'create')
    <a href="{{ route('admin.students.certificates.signers.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> إضافة موقع
    </a>
    @endcanPermission
</x-page-header>

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
    <x-filter-bar>
            <div class="col-md-3">
                <label class="form-label">بحث</label>
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="بحث بالاسم..." value="{{ $search }}">
                    <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label">الدور</label>
                <select name="role" class="form-select form-select-sm">
                    <option value="">الكل</option>
                    @foreach (\App\Models\Admin\Student\CertificateSigner::ROLES as $key => $label)
                        <option value="{{ $key }}" {{ $role === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <x-per-page-selector :auto="false" :perPage="$signers->perPage()" />
            </div>
        </x-filter-bar>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
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
                            <a href="{{ route('admin.students.certificates.signers.edit', $signer) }}" class="btn btn-sm btn-outline-primary" aria-label="تعديل" title="تعديل"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                            @endcanPermission
                            @canPermission('App\Models\Admin\Student\Certificate', 'delete')
                            <form method="POST" action="{{ route('admin.students.certificates.signers.destroy', $signer) }}" class="d-inline"
                                  onsubmit="return confirm('هل أنت متأكد من حذف هذا الموقع؟')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" aria-label="حذف" title="حذف"><i class="bi bi-trash" aria-hidden="true"></i></button>
                            </form>
                            @endcanPermission
                        </td>
                    </tr>
                @empty
                    <x-empty-row colspan="5" icon="bi-person-x" title="لا يوجد موقعون. أضف مدرباً ومديري مراكز ومشاريع مع صور تواقيعهم." />
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
