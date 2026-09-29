@extends('admin.layouts.master')

@section('title', 'بطاقات الفعاليات')

@section('content')
<x-page-header :title="'بطاقات الفعاليات'" :description="'فعاليات منفصلة ينشئها مدير المشروع ويحيلها للموافقة ثم الاعتماد'"
               :breadcrumb="[['label' => 'المشاريع'], ['label' => 'بطاقات الفعاليات']]">
    @canPermission('App\Models\Admin\EventCard', 'create')
        <a href="{{ route('admin.event-cards.create') }}" class="btn btn-brand">
            <i class="bi bi-plus-lg me-1"></i> بطاقة فعالية جديدة
        </a>
    @endcanPermission
</x-page-header>

<div class="table-container">
    <x-filter-bar>
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
        </x-filter-bar>

    <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead>
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
                        <a href="{{ route('admin.event-cards.show', $card) }}" class="btn btn-sm btn-outline-primary" aria-label="عرض" title="عرض"><i class="bi bi-eye" aria-hidden="true"></i></a>
                        @if (! $card->isLocked())
                            @canPermission('App\Models\Admin\EventCard', 'edit')
                                <a href="{{ route('admin.event-cards.edit', $card) }}" class="btn btn-sm btn-outline-secondary" aria-label="تعديل" title="تعديل"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                            @endcanPermission
                        @endif
                    </td>
                </tr>
            @empty
                <x-empty-row colspan="6" icon="bi-calendar-event" title="لا توجد بطاقات فعاليات" />
            @endforelse
        </tbody>
    </table>
    </div>
    <div class="p-3">{{ $cards->links() }}</div>
</div>
@endsection
