@extends('admin.layouts.master')

@section('title', 'إحصائيات العلاج الفيزيائي')

@section('content')
<x-page-header :title="'إحصائيات العلاج الفيزيائي'"
               :breadcrumb="[['label' => 'العلاج الفيزيائي'], ['label' => 'الإحصائيات']]" />

<div class="table-container mb-3">
    <x-filter-bar>
        <div class="col-12 col-md-4 filter-field">
            <label class="form-label" for="f-center_id">المركز</label>
            <select id="f-center_id" name="center_id" class="form-select">
                <option value="">كل المراكز</option>
                @foreach ($centers as $center)
                    <option value="{{ $center->id }}" {{ (int) $centerId === $center->id ? 'selected' : '' }}>{{ $center->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-3 filter-field">
            <label class="form-label" for="f-from_date">من</label>
            <input type="date" id="f-from_date" name="from_date" class="form-control" value="{{ $fromDate ?? '' }}">
        </div>
        <div class="col-6 col-md-3 filter-field">
            <label class="form-label" for="f-to_date">إلى</label>
            <input type="date" id="f-to_date" name="to_date" class="form-control" value="{{ $toDate ?? '' }}">
        </div>
    </x-filter-bar>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card text-center h-100">
            <div class="card-body">
                <h2 class="mb-0 text-primary">{{ number_format($totalPatients) }}</h2>
                <div class="text-muted small">إجمالي المرضى</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center h-100">
            <div class="card-body">
                <h2 class="mb-0 text-success">{{ number_format($activeCount) }}</h2>
                <div class="text-muted small">مرضى نشطون</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center h-100">
            <div class="card-body">
                <h2 class="mb-0 text-warning">{{ number_format($transferredCount) }}</h2>
                <div class="text-muted small">مرضى منقولون</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center h-100">
            <div class="card-body">
                <h2 class="mb-0 text-info">{{ number_format($totalSessions) }}</h2>
                <div class="text-muted small">إجمالي الجلسات</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header">المرضى حسب الجنس</div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="badge bg-primary-subtle text-primary">ذكور</span>
                    <strong>{{ number_format($maleCount) }}</strong>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="badge bg-danger-subtle text-danger">إناث</span>
                    <strong>{{ number_format($femaleCount) }}</strong>
                </div>
                <div class="progress" style="height: 24px;">
                    @if ($totalPatients > 0)
                    <div class="progress-bar bg-primary" style="width: {{ round($maleCount / $totalPatients * 100) }}%">
                        {{ round($maleCount / $totalPatients * 100) }}%
                    </div>
                    <div class="progress-bar bg-danger" style="width: {{ round($femaleCount / $totalPatients * 100) }}%">
                        {{ round($femaleCount / $totalPatients * 100) }}%
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header">المرضى حسب المركز</div>
            <div class="card-body table-responsive">
                <table class="table table-sm mb-0">
                    <tbody>
                        @forelse ($patientsPerCenter as $row)
                        <tr>
                            <td>{{ $row['center'] }}</td>
                            <td class="text-end"><x-status-badge tone="brand">{{ $row['total'] }}</x-status-badge></td>
                        </tr>
                        @empty
                        <tr><td class="text-center text-muted py-3">لا بيانات</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header">المرضى حسب الغرفة</div>
            <div class="card-body table-responsive">
                <table class="table table-sm mb-0">
                    <tbody>
                        @forelse ($patientsPerRoom as $row)
                        <tr>
                            <td>{{ $row['room'] }}</td>
                            <td class="text-end"><x-status-badge tone="brand">{{ $row['total'] }}</x-status-badge></td>
                        </tr>
                        @empty
                        <tr><td class="text-center text-muted py-3">لا بيانات</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card">
            <div class="card-header">الجلسات حسب المعالج</div>
            <div class="card-body table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>المعالج</th>
                            <th>عدد الجلسات</th>
                            <th>النسبة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sessionsPerTherapist as $row)
                        <tr>
                            <td>{{ $row['therapist'] }}</td>
                            <td>{{ $row['total'] }}</td>
                            <td style="min-width: 200px;">
                                @if ($totalSessions > 0)
                                <div class="progress" style="height: 12px;">
                                    <div class="progress-bar bg-success" style="width: {{ round($row['total'] / $totalSessions * 100) }}%"></div>
                                </div>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <x-empty-row colspan="3" title="لا جلسات بعد" />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection