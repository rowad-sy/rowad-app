@extends('admin.layouts.master')

@section('title', 'إحصائيات المشاريع')

@section('content')
<x-page-header title="إحصائيات المهام" description="مؤشرات أداء المهام والمشاريع"
               :breadcrumb="[['label' => 'المهام', 'url' => route('admin.projects.tasks.index')], ['label' => 'الإحصائيات']]" />

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><x-kpi label="إجمالي المهام" :value="$total" tone="brand" /></div>
    <div class="col-6 col-md-3"><x-kpi label="منفذة" :value="$completed" tone="success" /></div>
    <div class="col-6 col-md-3"><x-kpi label="قيد الانتظار" :value="$pendingCount" tone="warning" /></div>
    <div class="col-6 col-md-3"><x-kpi label="متأخرة" :value="$delayed" tone="danger" /></div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-4"><x-kpi label="قيد التنفيذ" :value="$inProgress" tone="info" /></div>
    <div class="col-6 col-md-4"><x-kpi label="تم تنفيذها" :value="$executed" tone="success" /></div>
    <div class="col-6 col-md-4"><x-kpi label="لم تنفذ" :value="$notExecuted" tone="danger" /></div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-6"><x-kpi label="فيها تأخير" :value="$withDelay" tone="warning" /></div>
    <div class="col-6 col-md-6"><x-kpi label="تمت التغطية الإعلامية" :value="$mediaDone" tone="brand" /></div>
</div>

<div class="table-container">
    <div class="p-3 border-bottom"><h5 class="mb-0">المهام حسب الشهر</h5></div>
    <div class="p-3">
        <div class="d-flex align-items-end gap-2" style="height:200px">
            @foreach (range(1, 12) as $m)
                @php $cnt = $byMonth[$m] ?? 0; $max = max($byMonth->max() ?: 1, 1); $h = $max > 0 ? round(($cnt / $max) * 180) : 0; @endphp
                <div class="d-flex flex-column align-items-center flex-fill">
                    <div class="small fw-bold">{{ $cnt }}</div>
                    <div style="height:{{ $h }}px; width:100%; background:var(--bs-primary); border-radius:4px 4px 0 0; min-height:{{ $cnt > 0 ? '4' : '0' }}px;"></div>
                    <div class="small mt-1">{{ now()->month($m)->locale('ar')->translatedFormat('M') }}</div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
