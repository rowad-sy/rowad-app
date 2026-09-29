@extends('admin.layouts.master')

@section('title', 'الشهادات المصدرة')

@section('content')
<x-page-header :title="'الشهادات المصدرة'" :description="'جميع الشهادات التي تم إصدارها'"
               :breadcrumb="[['label' => 'الطلاب'], ['label' => 'الشهادات المصدرة']]">
</div>

<div class="table-container">
    <x-filter-bar>
            <div class="col-md-3">
                <label class="form-label">بحث</label>
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="رقم الشهادة أو اسم الطالب..." value="{{ $search }}">
                    <button class="btn btn-outline-secondary"><i class="bi bi-search"></i></button>
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label">التصميم</label>
                <select name="design_id" class="form-select">
                    <option value="">الكل</option>
                    @foreach ($designs as $d)
                        <option value="{{ $d->id }}" {{ $designId == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <x-per-page-selector :auto="false" :perPage="$perPage ?? 10" />
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <a href="{{ route('admin.students.certificates.print-batch', ['design_id' => $designId]) }}"
                   class="btn btn-success w-100 {{ $certificates->isEmpty() ? 'disabled' : '' }}"
                   target="_blank">
                    <i class="bi bi-printer me-1"></i> طباعة الكل
                </a>
            </div>
        </x-filter-bar>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>رقم الشهادة</th>
                    <th>الطالب</th>
                    <th>التصميم</th>
                    <th>تاريخ الإصدار</th>
                    <th>الحالة</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($certificates as $cert)
                    <tr @if($cert->cancelled_at) style="opacity:0.5;text-decoration:line-through;" @endif>
                        <td>{{ $cert->id }}</td>
                        <td><code>{{ $cert->certificate_number }}</code></td>
                        <td>
                            <a href="{{ route('admin.students.show', $cert->student) }}" class="text-decoration-none">
                                {{ $cert->student->first_name_ar }} {{ $cert->student->last_name_ar }}
                            </a>
                            <br><small class="text-muted">{{ $cert->student->student_code }}</small>
                        </td>
                        <td>{{ $cert->design?->name ?? '—' }}</td>
                        <td>{{ $cert->issue_date?->format('Y-m-d') }}</td>
                        <td>
                            @if($cert->cancelled_at)
                                <span class="badge bg-danger">ملغاة</span>
                            @elseif ($cert->is_verified)
                                <span class="badge bg-success">تم التحقق</span>
                            @else
                                <span class="badge bg-secondary">صادرة</span>
                            @endif
                        </td>
                        <td>
                            @if(!$cert->cancelled_at)
                                <a href="{{ route('admin.students.certificates.preview', $cert) }}" class="btn btn-sm btn-outline-info" target="_blank" aria-label="عرض" title="عرض"><i class="bi bi-eye" aria-hidden="true"></i></a>
                                <form method="POST" action="{{ route('admin.students.certificates.cancel', $cert) }}" class="d-inline"
                                      onsubmit="return confirm('هل أنت متأكد من إلغاء هذه الشهادة؟ سيتم تحرير رقم الشهادة لإعادة استخدامه.')">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-danger" title="إلغاء الشهادة">
                                        <i class="bi bi-x-circle"></i>
                                    </button>
                                </form>
                            @endif
                            <x-audit-history :model="'App\Models\Admin\Student\Certificate'" :model-id="$cert->id" />
                        </td>
                    </tr>
                @empty
                    <x-empty-row colspan="7" icon="bi-file-earmark-x" title="لا يوجد شهادات مصدرة" />
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="p-3 d-flex justify-content-between align-items-center">
        <div class="text-muted small">إجمالي: {{ $certificates->total() }} شهادة</div>
        <div>{{ $certificates->links() }}</div>
    </div>
</x-page-header>
@endsection
