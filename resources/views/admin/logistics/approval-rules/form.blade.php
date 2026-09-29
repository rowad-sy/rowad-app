@extends('admin.logistics.layouts.master')

@section('title', isset($approvalRule) ? 'تعديل قاعدة موافقة' : 'إضافة قاعدة موافقة')

@section('logistics-content')
<x-page-header :title="isset($approvalRule) ? 'تعديل قاعدة الموافقة' : 'إضافة قاعدة موافقة'"
               :breadcrumb="[['label' => 'قواعد الموافقات', 'url' => route('admin.logistics.approval-rules.index')], ['label' => isset($approvalRule) ? $approvalRule->name : 'جديد']]" />

<div class="row">
    <div class="col-md-6">
        <div class="form-card">
            <form method="POST"
                  action="{{ isset($approvalRule) ? route('admin.logistics.approval-rules.update', $approvalRule) : route('admin.logistics.approval-rules.store') }}">
                @csrf
                @if (isset($approvalRule))
                    @method('PUT')
                @endif

                <div class="mb-3">
                    <label class="form-label">الاسم <span class="text-danger">*</span></label>
                    <input type="text" name="name"
                           class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $approvalRule->name ?? '') }}" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">الحد الأدنى <span class="text-danger">*</span></label>
                        <input type="number" name="min_amount"
                               class="form-control @error('min_amount') is-invalid @enderror"
                               value="{{ old('min_amount', $approvalRule->min_amount ?? '0') }}" required min="0" step="0.01">
                        @error('min_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">الحد الأعلى <span class="text-muted">(اختياري)</span></label>
                        <input type="number" name="max_amount"
                               class="form-control @error('max_amount') is-invalid @enderror"
                               value="{{ old('max_amount', $approvalRule->max_amount ?? '') }}" min="0" step="0.01">
                        <div class="form-text">اتركه فارغاً لعدم وجود حد أعلى</div>
                        @error('max_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mb-3 mt-3">
                    <label class="form-label">عدد الموافقات المطلوبة <span class="text-danger">*</span></label>
                    <input type="number" name="required_approvals"
                           class="form-control @error('required_approvals') is-invalid @enderror"
                           value="{{ old('required_approvals', $approvalRule->required_approvals ?? '1') }}" required min="1">
                    @error('required_approvals') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">المعتمدون</label>
                    <div class="border rounded p-3" style="max-height:250px;overflow-y:auto;">
                        @foreach ($users ?? [] as $user)
                            <div class="form-check">
                                <input type="checkbox" name="approver_ids[]" value="{{ $user->id }}"
                                       class="form-check-input"
                                       id="user_{{ $user->id }}"
                                       {{ in_array($user->id, old('approver_ids', $selectedApprovers ?? [])) ? 'checked' : '' }}>
                                <label class="form-check-label" for="user_{{ $user->id }}">
                                    {{ $user->name }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                    @error('approver_ids') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">ملاحظات</label>
                    <textarea name="notes" rows="3"
                              class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $approvalRule->notes ?? '') }}</textarea>
                    @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> حفظ
                    </button>
                    <a href="{{ route('admin.logistics.approval-rules.index') }}" class="btn btn-outline-secondary">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
