@extends('admin.layouts.master')

@section('title', 'بطاقات الفعاليات')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h4><i class="bi bi-calendar-event me-2 text-danger"></i>بطاقات الفعاليات</h4>
        <p>فعاليات منفصلة ينشئها مدير المشروع ويحيلها للموافقة ثم الاعتماد</p>
    </div>
    @canPermission('App\Models\Admin\EventCard', 'create')
        <a href="{{ route('admin.event-cards.create') }}" class="btn btn-brand">
            <i class="bi bi-plus-lg me-1"></i> بطاقة فعالية جديدة
        </a>
    @endcanPermission
</div>

<div class="table-container">
    <div class="p-3 border-bottom">
        <form method="GET" class="row g-2">
            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">كل الحالات</option>
                    @foreach ($statuses as $key => $label)
                        <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-funnel me-1"></i>تصفية</button>
            </div>
        </form>
    </div>

    <table class="table table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th>اسم الفعالية</th>
                <th>المشروع</th>
                <th>التاريخ</th>
                <th>المحال إليه</th>
                <th>الحالة</th>
                <th>إجراءات</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($cards as $card)
                <tr>
                    <td><a href="{{ route('admin.event-cards.show', $card) }}" class="text-decoration-none fw-medium">{{ $card->name }}</a></td>
                    <td class="text-muted">{{ $card->project?->name ?? '—' }}</td>
                    <td class="text-muted">{{ $card->event_date?->format('Y-m-d') ?? '—' }}</td>
                    <td class="text-muted">{{ $card->referredUser?->name ?? '—' }}</td>
                    <td><span class="badge {{ $card->statusBadgeClass() }}">{{ $card->statusLabel() }}</span></td>
                    <td>
                        <a href="{{ route('admin.event-cards.show', $card) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                        @if (! $card->isLocked())
                            @canPermission('App\Models\Admin\EventCard', 'edit')
                                <a href="{{ route('admin.event-cards.edit', $card) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                            @endcanPermission
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">
                        <i class="bi bi-calendar-event fs-3 d-block mb-2"></i> لا توجد بطاقات فعاليات
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <div class="p-3">{{ $cards->links() }}</div>
</div>
@endsection
