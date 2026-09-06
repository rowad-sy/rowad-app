@extends('admin.layouts.master')

@section('title', isset($plan) ? 'تعديل خطة إعلامية' : 'إضافة خطة إعلامية')

@section('content')
<div class="page-header">
    <h4>{{ isset($plan) ? 'تعديل الخطة الإعلامية' : 'إضافة خطة إعلامية' }}</h4>
    <p>
        <a href="{{ route('admin.media-plans.index') }}" class="text-decoration-none">الخطة الإعلامية</a>
        / {{ isset($plan) ? 'تعديل' : 'جديد' }}
    </p>
    <small class="text-muted">تُعبَّأ الخطة في الفترة من 25 إلى 30 من الشهر، ويدخلها مسؤول المشروع.</small>
</div>

<div class="form-card mb-3">
    <form method="POST"
          action="{{ isset($plan) ? route('admin.media-plans.update', $plan) : route('admin.media-plans.store') }}">
        @csrf
        @if (isset($plan)) @method('PUT') @endif

        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <label class="form-label">الشهر <span class="text-danger">*</span></label>
                <input type="date" name="month_date" class="form-control @error('month_date') is-invalid @enderror"
                       value="{{ old('month_date', isset($plan) ? $plan->month_date->format('Y-m-d') : now()->format('Y-m-d')) }}" required>
                @error('month_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">المركز</label>
                <select name="center_id" class="form-select @error('center_id') is-invalid @enderror">
                    <option value="">— اختر —</option>
                    @foreach ($centers as $c)
                        <option value="{{ $c->id }}" {{ old('center_id', $plan->center_id ?? '') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
                @error('center_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">المشروع</label>
                <select name="project_id" class="form-select @error('project_id') is-invalid @enderror">
                    <option value="">— اختر —</option>
                    @foreach ($projects as $p)
                        <option value="{{ $p->id }}" {{ old('project_id', $plan->project_id ?? '') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                    @endforeach
                </select>
                @error('project_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">ملاحظات عامة</label>
            <textarea name="note" rows="2" class="form-control @error('note') is-invalid @enderror">{{ old('note', $plan->note ?? '') }}</textarea>
            @error('note') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0">فعاليات الخطة</h6>
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addEventRow()"><i class="bi bi-plus-lg me-1"></i> إضافة فعالية</button>
        </div>

        @error('events')
            <div class="alert alert-danger py-2">
                @foreach ($errors->get('events') as $list)
                    @foreach ($list as $msg) <div>{{ $msg }}</div> @endforeach
                @endforeach
            </div>
        @enderror

        <div id="eventsContainer">
            @if (isset($plan) && $plan->events->count())
                @foreach ($plan->events as $ev)
                    @include('admin.media-plans._event_row', ['ev' => $ev, 'index' => $loop->index, 'users' => $users])
                @endforeach
            @else
                @include('admin.media-plans._event_row', ['ev' => null, 'index' => 0, 'users' => $users])
            @endif
        </div>

        <div class="d-flex gap-2 mt-3">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> حفظ</button>
            <a href="{{ route('admin.media-plans.index') }}" class="btn btn-outline-secondary">إلغاء</a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
let eventIndex = {{ isset($plan) ? $plan->events->count() : 1 }};

function eventRowTemplate() {
    return {!! json_encode(view('admin.media-plans._event_row', ['ev' => null, 'index' => '__INDEX__', 'users' => $users])->render()) !!};
}

function addEventRow() {
    const container = document.getElementById('eventsContainer');
    const html = eventRowTemplate().replace(/__INDEX__/g, eventIndex++);
    container.insertAdjacentHTML('beforeend', html);
    container.lastElementChild.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function removeEventRow(btn) {
    btn.closest('.event-card').remove();
}
</script>
@endpush