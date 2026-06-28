@extends('admin.layouts.master')

@section('title', $task->title)

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4>{{ $task->title }}</h4>
        <p>
            <a href="{{ route('admin.projects.tasks.index') }}" class="text-decoration-none">المهام</a>
            / {{ $task->title }}
        </p>
    </div>
    <a href="{{ route('admin.projects.tasks.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-right me-1"></i> عودة</a>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="table-container">
            <div class="p-3 border-bottom"><h5 class="mb-0">تفاصيل المهمة</h5></div>
            <div class="p-3">
                <table class="table table-bordered mb-0">
                    <tr><th style="width:200px">اسم المهمة</th><td>{{ $task->title }}</td></tr>
                    <tr><th>الغاية</th><td>{{ $task->purpose ?? '—' }}</td></tr>
                    <tr><th>مدة التنفيذ</th><td>{{ $task->start_date->format('Y-m-d') }} → {{ $task->end_date->format('Y-m-d') }}</td></tr>
                    <tr><th>تم الإنشاء بواسطة</th><td>{{ $task->createdBy?->name ?? '—' }}</td></tr>
                    <tr><th>مسندة إلى</th><td>{{ $task->assignedTo?->name ?? '—' }}</td></tr>
                    <tr><th>المركز</th><td>{{ $task->center?->name ?? '—' }}</td></tr>
                    <tr><th>الحالة</th>
                        <td>
                            @php $m = ['pending'=>'bg-warning text-dark|قيد الانتظار','in_progress'=>'bg-info|قيد التنفيذ','completed'=>'bg-success|منفذة','delayed'=>'bg-danger|متأخرة','cancelled'=>'bg-secondary|ملغاة']; $s = explode('|', $m[$task->status] ?? 'bg-secondary|'.$task->status); @endphp
                            <span class="badge {{ $s[0] }}">{{ $s[1] }}</span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        @if ($task->needs_costs || $task->needs_equipment || $task->needs_media_coverage)
        <div class="table-container mt-3">
            <div class="p-3 border-bottom"><h5 class="mb-0">متطلبات إضافية</h5></div>
            <div class="p-3">
                <table class="table table-bordered mb-0">
                    @if ($task->needs_media_coverage)
                    <tr><th style="200px">تغطية إعلامية</th><td>مطلوبة</td></tr>
                    @endif
                    @if ($task->needs_costs)
                    <tr><th>تفاصيل التكاليف</th><td>{{ $task->costs_details ?? '—' }}</td></tr>
                    @endif
                    @if ($task->needs_equipment)
                    <tr><th>تفاصيل التجهيزات</th><td>{{ $task->equipment_details ?? '—' }}</td></tr>
                    @endif
                </table>
            </div>
        </div>
        @endif
    </div>

    <div class="col-lg-4">
        <div class="table-container">
            <div class="p-3 border-bottom"><h5 class="mb-0">متابعة التنفيذ</h5></div>
            <div class="p-3">
                <form method="POST" action="{{ route('admin.projects.tasks.update-status', $task) }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-bold small">هل تم تنفيذ المهمة؟</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input type="radio" name="executed" value="1" class="form-check-input" id="exeYes" {{ $task->executed === true ? 'checked' : '' }} onchange="toggleExecFields()">
                                <label class="form-check-label" for="exeYes">نعم</label>
                            </div>
                            <div class="form-check">
                                <input type="radio" name="executed" value="0" class="form-check-input" id="exeNo" {{ $task->executed === false ? 'checked' : '' }} onchange="toggleExecFields()">
                                <label class="form-check-label" for="exeNo">لا</label>
                            </div>
                            <div class="form-check">
                                <input type="radio" name="executed" value="" class="form-check-input" id="exeNa" {{ $task->executed === null ? 'checked' : '' }} onchange="toggleExecFields()">
                                <label class="form-check-label" for="exeNa">لم يحدد</label>
                            </div>
                        </div>
                    </div>

                    <div id="notExecutedReason" style="display:none" class="mb-3">
                        <label class="form-label small">سبب عدم التنفيذ</label>
                        <textarea name="not_executed_reason" rows="2" class="form-control form-control-sm">{{ $task->not_executed_reason }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">هل هناك تأخير في التنفيذ؟</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input type="radio" name="has_delay" value="1" class="form-check-input" id="delayYes" {{ $task->has_delay === true ? 'checked' : '' }} onchange="toggleDelayFields()">
                                <label class="form-check-label" for="delayYes">نعم</label>
                            </div>
                            <div class="form-check">
                                <input type="radio" name="has_delay" value="0" class="form-check-input" id="delayNo" {{ $task->has_delay === false ? 'checked' : '' }} onchange="toggleDelayFields()">
                                <label class="form-check-label" for="delayNo">لا</label>
                            </div>
                            <div class="form-check">
                                <input type="radio" name="has_delay" value="" class="form-check-input" id="delayNa" {{ $task->has_delay === null ? 'checked' : '' }} onchange="toggleDelayFields()">
                                <label class="form-check-label" for="delayNa">لم يحدد</label>
                            </div>
                        </div>
                    </div>

                    <div id="delayReasonField" style="display:none" class="mb-3">
                        <label class="form-label small">سبب التأخير</label>
                        <textarea name="delay_reason" rows="2" class="form-control form-control-sm">{{ $task->delay_reason }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">هل تمت التغطية الإعلامية؟</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input type="radio" name="media_coverage_done" value="1" class="form-check-input" id="mediaYes" {{ $task->media_coverage_done === true ? 'checked' : '' }} onchange="toggleMediaFields()">
                                <label class="form-check-label" for="mediaYes">نعم</label>
                            </div>
                            <div class="form-check">
                                <input type="radio" name="media_coverage_done" value="0" class="form-check-input" id="mediaNo" {{ $task->media_coverage_done === false ? 'checked' : '' }} onchange="toggleMediaFields()">
                                <label class="form-check-label" for="mediaNo">لا</label>
                            </div>
                            <div class="form-check">
                                <input type="radio" name="media_coverage_done" value="" class="form-check-input" id="mediaNa" {{ $task->media_coverage_done === null ? 'checked' : '' }} onchange="toggleMediaFields()">
                                <label class="form-check-label" for="mediaNa">غير مطبق</label>
                            </div>
                        </div>
                    </div>

                    <div id="noMediaReason" style="display:none" class="mb-3">
                        <label class="form-label small">سبب عدم التغطية</label>
                        <textarea name="no_media_coverage_reason" rows="2" class="form-control form-control-sm">{{ $task->no_media_coverage_reason }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small">ملاحظات التنفيذ</label>
                        <textarea name="execution_notes" rows="2" class="form-control form-control-sm">{{ $task->execution_notes }}</textarea>
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-check-lg me-1"></i> حفظ التحديث</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function toggleExecFields() {
    var val = document.querySelector('[name="executed"]:checked');
    document.getElementById('notExecutedReason').style.display = val && val.value === '0' ? 'block' : 'none';
}
function toggleDelayFields() {
    var val = document.querySelector('[name="has_delay"]:checked');
    document.getElementById('delayReasonField').style.display = val && val.value === '1' ? 'block' : 'none';
}
function toggleMediaFields() {
    var val = document.querySelector('[name="media_coverage_done"]:checked');
    document.getElementById('noMediaReason').style.display = val && val.value === '0' ? 'block' : 'none';
}
document.addEventListener('DOMContentLoaded', function () { toggleExecFields(); toggleDelayFields(); toggleMediaFields(); });
</script>
@endpush
