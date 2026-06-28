@extends('admin.layouts.master')

@section('title', isset($task) ? 'تعديل مهمة' : 'إضافة مهمة')

@section('content')
<div class="page-header">
    <h4>{{ isset($task) ? 'تعديل المهمة' : 'إضافة مهمة' }}</h4>
    <p>
        <a href="{{ route('admin.projects.tasks.index') }}" class="text-decoration-none">المهام</a>
        / {{ isset($task) ? $task->title : 'جديد' }}
    </p>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="form-card">
            <form method="POST"
                  action="{{ isset($task) ? route('admin.projects.tasks.update', $task) : route('admin.projects.tasks.store') }}">
                @csrf
                @if (isset($task)) @method('PUT') @endif

                <div class="mb-3">
                    <label class="form-label">اسم المهمة <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                           value="{{ old('title', $task->title ?? '') }}" required>
                    @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">الغاية من المهمة</label>
                    <textarea name="purpose" rows="3" class="form-control @error('purpose') is-invalid @enderror">{{ old('purpose', $task->purpose ?? '') }}</textarea>
                    @error('purpose') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">تاريخ البداية <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror"
                               value="{{ old('start_date', $task->start_date ?? now()->format('Y-m-d')) }}" required>
                        @error('start_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">تاريخ النهاية <span class="text-danger">*</span></label>
                        <input type="date" name="end_date" class="form-control @error('end_date') is-invalid @enderror"
                               value="{{ old('end_date', $task->end_date ?? now()->addDays(7)->format('Y-m-d')) }}" required>
                        @error('end_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">المسند إليه <span class="text-danger">*</span></label>
                        <select name="assigned_to" class="form-select @error('assigned_to') is-invalid @enderror" required>
                            <option value="">— اختر —</option>
                            @foreach ($users as $u)
                                <option value="{{ $u->id }}" {{ old('assigned_to', $task->assigned_to ?? '') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                            @endforeach
                        </select>
                        @error('assigned_to') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">المركز</label>
                        <select name="center_id" class="form-select @error('center_id') is-invalid @enderror">
                            <option value="">— اختر —</option>
                            @foreach ($centers as $c)
                                <option value="{{ $c->id }}" {{ old('center_id', $task->center_id ?? '') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                        @error('center_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                @if (!isset($task))
                <div class="mb-3">
                    <label class="form-label">تغطية إعلامية</label>
                    <div class="form-check form-switch">
                        <input type="hidden" name="needs_media_coverage" value="0">
                        <input type="checkbox" name="needs_media_coverage" class="form-check-input" value="1" role="switch" id="mediaSwitch" onchange="toggleField('mediaDetails', this.checked)">
                        <label class="form-check-label" for="mediaSwitch">تحتاج تغطية إعلامية</label>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">تكاليف</label>
                    <div class="form-check form-switch">
                        <input type="hidden" name="needs_costs" value="0">
                        <input type="checkbox" name="needs_costs" class="form-check-input" value="1" role="switch" id="costsSwitch" onchange="toggleField('costsDetails', this.checked)">
                        <label class="form-check-label" for="costsSwitch">تحتاج تكاليف محددة</label>
                    </div>
                    <div id="costsDetails" style="display:none" class="mt-2">
                        <textarea name="costs_details" rows="2" class="form-control" placeholder="تفاصيل التكاليف">{{ old('costs_details') }}</textarea>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">تجهيزات</label>
                    <div class="form-check form-switch">
                        <input type="hidden" name="needs_equipment" value="0">
                        <input type="checkbox" name="needs_equipment" class="form-check-input" value="1" role="switch" id="equipmentSwitch" onchange="toggleField('equipmentDetails', this.checked)">
                        <label class="form-check-label" for="equipmentSwitch">تحتاج تجهيزات معينة</label>
                    </div>
                    <div id="equipmentDetails" style="display:none" class="mt-2">
                        <textarea name="equipment_details" rows="2" class="form-control" placeholder="تفاصيل التجهيزات">{{ old('equipment_details') }}</textarea>
                    </div>
                </div>
                @else
                <div class="mb-3">
                    <label class="form-label">الحالة</label>
                    <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                        @foreach (['pending' => 'قيد الانتظار', 'in_progress' => 'قيد التنفيذ', 'completed' => 'منفذة', 'delayed' => 'متأخرة', 'cancelled' => 'ملغاة'] as $val => $label)
                            <option value="{{ $val }}" {{ old('status', $task->status ?? '') === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                @endif

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> حفظ</button>
                    <a href="{{ route('admin.projects.tasks.index') }}" class="btn btn-outline-secondary">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function toggleField(id, show) {
    document.getElementById(id).style.display = show ? 'block' : 'none';
}
</script>
@endpush
