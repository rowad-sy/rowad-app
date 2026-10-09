@extends('admin.layouts.master')

@section('title', isset($ad) ? 'تعديل طلب تصميم' : 'طلب تصميم إعلاني جديد')

@section('content')
<x-page-header :title="isset($ad) ? 'تعديل طلب التصميم' : 'طلب تصميم إعلاني جديد'"
               :description="'طلب تصميم إعلاني لدورة أو نشاط — يمر بمدير المشاريع ثم قسم روادنا ثم المصمم'"
               :breadcrumb="[['label' => 'طلبات التصميم', 'url' => route('admin.ad-design-requests.index')], ['label' => isset($ad) ? 'تعديل' : 'جديد']]" />

@if ($errors->any())
<div class="alert alert-danger py-2">{{ $errors->first() }}</div>
@endif

<div class="form-card mb-3">
    <form method="POST" action="{{ isset($ad) ? route('admin.ad-design-requests.update', $ad) : route('admin.ad-design-requests.store') }}">
        @csrf
        @if (isset($ad)) @method('PUT') @endif

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label">عنوان الطلب <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                       value="{{ old('title', $ad->title ?? '') }}" placeholder="مثال: إعلان دورة اللغة الإنجليزية — الدفعة الثالثة" required>
                @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-3">
                <label class="form-label">المشروع</label>
                <select name="project_id" class="form-select @error('project_id') is-invalid @enderror">
                    <option value="">— اختر —</option>
                    @foreach ($projects as $p)
                        <option value="{{ $p->id }}" {{ old('project_id', $ad->project_id ?? '') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                    @endforeach
                </select>
                 @error('project_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
             </div>
        </div>

        <div class="mb-3">
            @php
                $selected = collect(old('center_ids', $selectedCenterIds ?? ($ad ? [$ad->center_id] : [])))
                    ->map(fn ($v) => (int) $v)->filter()->unique()->values()->all();
            @endphp
            <label class="form-label">المراكز المستفيدة <span class="text-muted small">(يمكن اختيار أكثر من مركز)</span></label>
            <div class="border rounded p-2" style="max-height: 160px; overflow-y: auto;">
                <div class="row g-2">
                    @foreach ($centers as $c)
                        <div class="col-md-6 col-12">
                            <label class="check-chip">
                                <input type="checkbox" name="center_ids[]" value="{{ $c->id }}" class="check-chip-input"
                                       @checked(in_array($c->id, $selected, true))>
                                <span>{{ $c->name }}</span>
                            </label>
                        </div>
                    @endforeach
                </div>
                @if ($centers->isEmpty())
                    <div class="text-muted small p-2">لا توجد مراكز بعد.</div>
                @endif
            </div>
            @error('center_ids') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            @error('center_ids.*') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label">المتطلبات والتفاصيل</label>
                <textarea name="description" rows="4" class="form-control @error('description') is-invalid @enderror"
                          placeholder="نصوص الإعلان، الألوان، القياسات، الصور المطلوب تضمينها...">{{ old('description', $ad->description ?? '') }}</textarea>
                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-3">
                <label class="form-label">المطلوب قبل</label>
                <input type="date" name="due_date" class="form-control @error('due_date') is-invalid @enderror"
                       value="{{ old('due_date', isset($ad) && $ad->due_date ? $ad->due_date->format('Y-m-d') : '') }}">
                @error('due_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            @if (! isset($ad))
            <div class="col-md-3">
                <label class="form-label">مدير المشاريع</label>
                <select name="refer_to_pm2_id" class="form-select @error('refer_to_pm2_id') is-invalid @enderror">
                    <option value="">تلقائي — الافتراضي</option>
                    @foreach ($users as $u)
                        <option value="{{ $u->id }}" @selected((int) old('refer_to_pm2_id', $tentativePm2Id ?? 0) === (int) $u->id)>{{ $u->name }}</option>
                    @endforeach
                </select>
                @error('refer_to_pm2_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>
            @endif
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> حفظ</button>
            <a href="{{ route('admin.ad-design-requests.index') }}" class="btn btn-outline-secondary">إلغاء</a>
        </div>
    </form>
</div>
@endsection
