{{-- شريط بحث وفلاتر (GET) بسلوك واحد: تُطبَّق الفلاتر بزر «تطبيق» فقط (لا إرسال تلقائي)، والعودة للصفحة الأولى تلقائية.
     الحقول في الـ slot داخل صف Bootstrap. clear = رابط المسح (الافتراضي الصفحة الحالية)، active = عدد الفلاتر المفعّلة (يُحسب من الطلب افتراضيًا) --}}
@props(['clear' => null, 'active' => null])

@php
    $active ??= collect(request()->except(['page', 'per_page']))->filter(fn ($v) => filled($v) && $v !== 'all')->count();
    $clear ??= url()->current();
@endphp
<form method="GET" class="filter-bar" role="search">
    <div class="row g-2 align-items-end">
        {{ $slot }}
        <div class="col-auto filter-actions">
            <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel me-1" aria-hidden="true"></i>تطبيق</button>
            @if ($active > 0)
                <a href="{{ $clear }}" class="btn btn-outline-secondary btn-sm">مسح الفلاتر ({{ $active }})</a>
            @endif
        </div>
    </div>
</form>
