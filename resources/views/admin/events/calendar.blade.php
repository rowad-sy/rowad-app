@extends('admin.layouts.master')

@section('title', 'تقويم الفعاليات')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h4><i class="bi bi-calendar3 me-2 text-danger"></i>تقويم الفعاليات</h4>
        <p>الفعاليات التي تمت وستتم في المؤسسة (بطاقات الفعاليات المعتمدة)</p>
    </div>
    <a href="{{ route('admin.portal') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-right me-1"></i> رجوع
    </a>
</div>

<div class="cal-wrap">
    <div class="cal-head">
        <a href="{{ route('admin.events-calendar', ['y' => $prev->year, 'm' => $prev->month]) }}" class="btn btn-sm btn-outline-brand">
            <i class="bi bi-chevron-right"></i> السابق
        </a>
        <h5 class="mb-0 fw-bold">{{ $monthLabel }}</h5>
        <a href="{{ route('admin.events-calendar', ['y' => $next->year, 'm' => $next->month]) }}" class="btn btn-sm btn-outline-brand">
            التالي <i class="bi bi-chevron-left"></i>
        </a>
    </div>

    <div class="cal-grid">
        @foreach ($dayNames as $dn)
            <div class="cal-dow">{{ $dn }}</div>
        @endforeach

        @foreach ($weeks as $week)
            @foreach ($week as $cell)
                @if (is_null($cell))
                    <div class="cal-cell empty"></div>
                @else
                    @php
                        $day = $cell['day'];
                        $events = $cell['events'];
                        $date = \Illuminate\Support\Carbon::create($year, $month, $day);
                        $isToday = $date->isSameDay($today);
                    @endphp
                    <div class="cal-cell {{ $isToday ? 'today' : '' }}">
                        <span class="cal-daynum">{{ $day }}</span>
                        @foreach ($events as $card)
                            @php $done = $card->event_date->lt($today->copy()->startOfDay()); @endphp
                            <a href="{{ route('admin.event-cards.show', $card) }}" class="cal-event {{ $done ? 'done' : '' }}"
                               title="{{ $card->name }}{{ $card->project ? ' — '.$card->project->name : '' }}">
                                {{ $card->name }}
                            </a>
                        @endforeach
                    </div>
                @endif
            @endforeach
        @endforeach
    </div>
</div>

<div class="d-flex align-items-center gap-3 mt-3 small text-muted flex-wrap">
    <span><i class="bi bi-circle-fill text-danger me-1"></i> فعالية قادمة</span>
    <span><i class="bi bi-circle-fill text-secondary me-1"></i> فعالية سابقة</span>
    <span>عدد الفعاليات هذا الشهر: <strong>{{ $cards->count() }}</strong></span>
</div>
@endsection
