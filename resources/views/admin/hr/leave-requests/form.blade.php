@extends('admin.layouts.master')

@section('title', 'طلب إجازة')

@section('content')
<div class="page-header">
    <h4>طلب إجازة</h4>
    <p>
        <a href="{{ route('admin.hr.leave-requests.index') }}" class="text-decoration-none">طلبات الإجازات</a>
        / طلب جديد
    </p>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-card">
            <form method="POST" action="{{ route('admin.hr.leave-requests.store') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label">الموظف</label>
                    <input type="text" class="form-control" value="{{ $employee->first_name_ar }} {{ $employee->last_name_ar }} ({{ $employee->employee_code }})" disabled>
                </div>

                <div class="mb-3">
                    <label class="form-label">نوع الإجازة <span class="text-danger">*</span></label>
                    <select name="leave_type_id" class="form-select @error('leave_type_id') is-invalid @enderror" required>
                        <option value="">— اختر —</option>
                        @foreach ($leaveTypes as $type)
                            <option value="{{ $type->id }}" {{ old('leave_type_id') == $type->id ? 'selected' : '' }}
                                data-color="{{ $type->color }}" data-icon="{{ $type->icon }}">
                                {{ $type->name_ar }} ({{ $type->annual_days }} يوم/سنة)
                            </option>
                        @endforeach
                    </select>
                    @error('leave_type_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">تاريخ البداية <span class="text-danger">*</span></label>
                    <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror"
                           value="{{ old('start_date', now()->format('Y-m-d')) }}" required>
                    @error('start_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">تاريخ النهاية <span class="text-danger">*</span></label>
                    <input type="date" name="end_date" class="form-control @error('end_date') is-invalid @enderror"
                           value="{{ old('end_date', now()->format('Y-m-d')) }}" required>
                    @error('end_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">السبب</label>
                    <textarea name="reason" class="form-control @error('reason') is-invalid @enderror" rows="3">{{ old('reason') }}</textarea>
                    @error('reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                @if ($balances->isNotEmpty())
                <div class="mb-3 p-3 rounded" style="background:#f8f9fa;">
                    <label class="form-label fw-bold mb-2">رصيد الإجازات المتاح</label>
                    <table class="table table-sm table-borderless mb-0">
                        @foreach ($balances as $balance)
                            <tr>
                                <td>
                                    <span class="badge" style="background:{{ $balance->leaveType?->color ?? '#6c757d' }}">
                                        <i class="bi {{ $balance->leaveType?->icon ?? 'bi-calendar' }} me-1"></i>
                                        {{ $balance->leaveType?->name_ar }}
                                    </span>
                                </td>
                                <td class="fw-bold {{ $balance->remaining_days <= 0 ? 'text-danger' : 'text-success' }}">
                                    {{ $balance->remaining_days }} يوم
                                </td>
                                <td class="text-muted small">(من أصل {{ $balance->total_days }})</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
                @endif

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-send me-1"></i> إرسال الطلب
                </button>
                <a href="{{ route('admin.hr.leave-requests.index') }}" class="btn btn-outline-secondary">إلغاء</a>
            </form>
        </div>
    </div>
</div>
@endsection
