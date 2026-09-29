{{-- صف فارغ في جدول: يميّز «لا توجد بيانات» عن «لا توجد نتائج تطابق الفلاتر» --}}
@props(['colspan', 'title' => 'لا توجد بيانات', 'icon' => 'bi-inbox', 'hint' => null])

@php
    $filtered = collect(request()->except(['page', 'per_page']))->filter(fn ($v) => filled($v) && $v !== 'all')->isNotEmpty();
@endphp
<tr>
    <td colspan="{{ $colspan }}">
        @if ($filtered)
            <x-empty-state icon="bi-search" title="لا توجد نتائج تطابق الفلاتر" hint="جرّب تعديل البحث أو مسح الفلاتر.">
                <a href="{{ url()->current() }}" class="btn btn-sm btn-outline-secondary mt-2">مسح الفلاتر</a>
            </x-empty-state>
        @else
            <x-empty-state :icon="$icon" :title="$title" :hint="$hint" />
        @endif
    </td>
</tr>
