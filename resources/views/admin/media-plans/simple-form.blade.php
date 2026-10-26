@extends('admin.layouts.master')

@section('title', isset($plan) ? 'تعديل خطة إعلامية' : 'إضافة خطة إعلامية')

@section('content')
@php
    $isEditing = isset($plan);
    $oldEvents = old('events');
    $selectedCenterId = $isEditing ? $plan->center_id : ($defaultCenterId ?? null);
    $selectedProjectId = $isEditing ? $plan->project_id : ($defaultProjectId ?? null);
    $entries = is_array($oldEvents) && count($oldEvents)
        ? collect($oldEvents)->map(fn ($event) => (object) $event)
        : ($isEditing ? $plan->events : collect([(object) []]));
@endphp

<x-page-header :title="$isEditing ? 'تعديل الخطة الإعلامية' : 'خطة إعلامية جديدة'"
               description="أدخل فعاليات الخطة ومددها الزمنية. تُحال بعد الحفظ إلى دور مدير المشاريع."
               :breadcrumb="[['label' => 'الخطة الإعلامية', 'url' => route('admin.media-plans.index')], ['label' => $isEditing ? 'تعديل' : 'جديد']]" />

<div class="form-card">
    <form method="POST" action="{{ $isEditing ? route('admin.media-plans.update', $plan) : route('admin.media-plans.store') }}">
        @csrf
        @if ($isEditing) @method('PUT') @endif

        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <label class="form-label">شهر الخطة <span class="text-danger">*</span></label>
                <input type="month" name="month_date" class="form-control @error('month_date') is-invalid @enderror"
                       value="{{ old('month_date', $isEditing ? $plan->month_date->format('Y-m') : now()->format('Y-m')) }}" required>
                @error('month_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">المركز <span class="text-danger">*</span></label>
                <select name="center_id" class="form-select @error('center_id') is-invalid @enderror" required>
                    <option value="">— اختر المركز —</option>
                    @foreach ($centers as $center)
                        <option value="{{ $center->id }}" @selected((int) old('center_id', $selectedCenterId ?? 0) === $center->id)>{{ $center->name }}</option>
                    @endforeach
                </select>
                @error('center_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">المشروع</label>
                <select name="project_id" class="form-select @error('project_id') is-invalid @enderror">
                    <option value="">— غير مرتبط بمشروع محدد —</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}" @selected((int) old('project_id', $selectedProjectId ?? 0) === $project->id)>{{ $project->name }}</option>
                    @endforeach
                </select>
                @error('project_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">ملاحظات عامة</label>
            <textarea name="note" rows="2" class="form-control">{{ old('note', $plan->note ?? '') }}</textarea>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0">الفعاليات</h6>
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addSimpleEventRow()">
                <i class="bi bi-plus-lg me-1" aria-hidden="true"></i> إضافة فعالية
            </button>
        </div>
        @error('events') <div class="alert alert-danger py-2">{{ $message }}</div> @enderror
        @error('role_assignment') <div class="alert alert-danger py-2">{{ $message }}</div> @enderror

        <div id="simpleEventsContainer">
            @foreach ($entries as $index => $event)
                @include('admin.media-plans._simple_event_row', ['event' => $event, 'index' => $index])
            @endforeach
        </div>

        <div class="d-flex gap-2 mt-3">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-send me-1" aria-hidden="true"></i> {{ $isEditing ? 'حفظ التعديلات' : 'حفظ وإحالة لمدير المشاريع' }}
            </button>
            <a href="{{ $isEditing ? route('admin.media-plans.show', $plan) : route('admin.media-plans.index') }}" class="btn btn-outline-secondary">إلغاء</a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
let simpleEventIndex = {{ $entries->count() }};

function simpleEventRowTemplate() {
    return {!! json_encode(view('admin.media-plans._simple_event_row', ['event' => (object) [], 'index' => '__INDEX__'])->render()) !!};
}

function addSimpleEventRow() {
    const container = document.getElementById('simpleEventsContainer');
    container.insertAdjacentHTML('beforeend', simpleEventRowTemplate().replace(/__INDEX__/g, simpleEventIndex++));
    container.lastElementChild.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function removeSimpleEventRow(button) {
    const container = document.getElementById('simpleEventsContainer');
    if (container.children.length <= 1) {
        alert('يجب أن تحتوي الخطة على فعالية واحدة على الأقل.');
        return;
    }
    button.closest('.simple-event-card').remove();
}
</script>
@endpush
