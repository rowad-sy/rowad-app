{{-- شريط بحث وفلاتر (GET) مع زرّي تطبيق ومسح. الحقول في الـ slot داخل صف Bootstrap.
     clear = رابط المسح؛ active = عدد الفلاتر المفعّلة --}}
@props(['clear' => null, 'active' => 0])

<form method="GET" class="filter-bar">
    <div class="row g-2 align-items-end">
        {{ $slot }}
        <div class="col-auto filter-actions">
            <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel me-1" aria-hidden="true"></i>تطبيق</button>
            @if ($active > 0 && $clear)
                <a href="{{ $clear }}" class="btn btn-outline-secondary btn-sm">مسح ({{ $active }})</a>
            @endif
        </div>
    </div>
</form>
