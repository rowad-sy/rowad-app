@extends('admin.layouts.master')

@section('title', 'إحصائيات المشاريع')

@section('content')
<div class="page-header">
    <h4>إحصائيات المهام</h4>
    <p>مؤشرات أداء المهام والمشاريع</p>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card bg-primary text-white p-3 rounded-3">
            <div class="fs-3 fw-bold">{{ $total }}</div>
            <div class="small">إجمالي المهام</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card bg-success text-white p-3 rounded-3">
            <div class="fs-3 fw-bold">{{ $completed }}</div>
            <div class="small">منفذة</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card bg-warning text-dark p-3 rounded-3">
            <div class="fs-3 fw-bold">{{ $pendingCount }}</div>
            <div class="small">قيد الانتظار</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card bg-danger text-white p-3 rounded-3">
            <div class="fs-3 fw-bold">{{ $delayed }}</div>
            <div class="small">متأخرة</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card bg-info text-white p-3 rounded-3">
            <div class="fs-3 fw-bold">{{ $inProgress }}</div>
            <div class="small">قيد التنفيذ</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card p-3 rounded-3 border">
            <div class="fs-3 fw-bold text-success">{{ $executed }}</div>
            <div class="small text-muted">تم تنفيذها</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card p-3 rounded-3 border">
            <div class="fs-3 fw-bold text-danger">{{ $notExecuted }}</div>
            <div class="small text-muted">لم تنفذ</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="stat-card p-3 rounded-3 border">
            <div class="fs-3 fw-bold text-warning">{{ $withDelay }}</div>
            <div class="small text-muted">فيها تأخير</div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="stat-card p-3 rounded-3 border">
            <div class="fs-3 fw-bold text-primary">{{ $mediaDone }}</div>
            <div class="small text-muted">تمت التغطية الإعلامية</div>
        </div>
    </div>
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
