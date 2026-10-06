@extends('admin.layouts.master')

@section('title', isset($movementPlan) ? 'تعديل خطة حركة' : 'خطة حركة جديدة')

@section('content')
@php $plan = $movementPlan ?? null; @endphp
<x-page-header :title="$plan ? 'تعديل خطة الحركة ' . $plan->request_number : 'خطة حركة جديدة'"
               description="خطة شهرية واحدة تحوي عدة حركات — تُنشأ عادةً من مدير المشروع ثم تُمرَّر إلى إدارة المشاريع فمسؤول الحركة."
               :breadcrumb="[['label' => 'خطة الحركة', 'url' => route('admin.movement-plans.index')], ['label' => $plan ? 'تعديل' : 'جديد']]" />

<div class="row">
    <div class="col-lg-10">
        <div class="form-card">
            <form method="POST" action="{{ $plan ? route('admin.movement-plans.update', $plan) : route('admin.movement-plans.store') }}">
                @csrf
                @if ($plan) @method('PUT') @endif

                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label class="form-label">شهر الخطة <span class="text-danger">*</span></label>
                        <input type="month" name="plan_month" class="form-control @error('plan_month') is-invalid @enderror"
                               value="{{ old('plan_month', $plan?->plan_month?->format('Y-m') ?? now()->format('Y-m')) }}" required>
                        @error('plan_month') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">المركز</label>
                        <select name="center_id" class="form-select">
                            <option value="">— اختر —</option>
                            @foreach ($centers as $c)
                                <option value="{{ $c->id }}" {{ old('center_id', $plan?->center_id) == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">المشروع</label>
                        <select name="project_id" class="form-select">
                            <option value="">— اختر —</option>
                            @foreach ($projects as $p)
                                <option value="{{ $p->id }}" {{ old('project_id', $plan?->project_id) == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">إحالة المراجعة إلى <span class="text-danger">*</span></label>
                        <select name="refer_to_pm2_id" class="user-picker @error('refer_to_pm2_id') is-invalid @enderror"
                                data-placeholder="ابحث عن المستخدم..." required>
                            <option value="">— اختر —</option>
                            @foreach ($users as $u)
                                <option value="{{ $u->id }}" @selected((int) old('refer_to_pm2_id', $plan?->refer_to_pm2_id ?? $defaultReferralId ?? 0) === $u->id)>
                                    {{ $u->name }} ({{ $u->email }})
                                </option>
                            @endforeach
                        </select>
                        @error('refer_to_pm2_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        <div class="form-text">المُرسَّلة افتراضياً لإدارة المشاريع — يمكن اختيار أي مستخدم آخر للمرونة.</div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">ملاحظات عامة على الخطة</label>
                    <textarea name="notes" rows="2" class="form-control">{{ old('notes', $plan?->notes) }}</textarea>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">حركات الخطة</h6>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addMovementRow()">
                        <i class="bi bi-plus-lg me-1"></i> إضافة حركة
                    </button>
                </div>

                @error('entries')
                    <div class="alert alert-danger py-2 small">يجب إدخال حركة واحدة على الأقل.</div>
                @enderror

                <div id="movementEntriesContainer">
                    @php
                        $oldEntries = old('entries');
                        $renderEntries = $plan
                            ? $plan->entries
                            : (is_array($oldEntries) && count($oldEntries) ? collect($oldEntries)->map(fn ($r) => (object) (is_array($r) ? $r : [])) : collect([null]));
                        $entryIndex = 0;
                    @endphp
                    @foreach ($renderEntries as $en)
                        @include('admin.movement-plans._movement_row', ['en' => $en, 'index' => $entryIndex++])
                    @endforeach
                </div>

                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send me-1"></i> {{ $plan ? 'حفظ التعديلات' : 'إنشاء وإحالة للمراجعة' }}
                    </button>
                    <a href="{{ $plan ? route('admin.movement-plans.show', $plan) : route('admin.movement-plans.index') }}" class="btn btn-outline-secondary">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let movementIndex = {{ $entryIndex }};

function movementRowTemplate() {
    return {!! json_encode(view('admin.movement-plans._movement_row', ['en' => null, 'index' => '__INDEX__'])->render()) !!};
}

function addMovementRow() {
    const container = document.getElementById('movementEntriesContainer');
    const html = movementRowTemplate().replace(/__INDEX__/g, movementIndex++);
    container.insertAdjacentHTML('beforeend', html);
    container.lastElementChild.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function removeMovementRow(btn) {
    btn.closest('.movement-entry-card').remove();
}
</script>
@endpush
