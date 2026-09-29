{{-- قسم قابل للطي (details) بعنوان وعدد؛ المحتوى يبقى في الصفحة ويمكن فتحه بالضغط أو لوحة المفاتيح --}}
@props(['title', 'count' => null, 'icon' => null, 'open' => false])

<details class="fold" @if ($open) open @endif>
    <summary>
        @if ($icon)<i class="bi {{ $icon }}" aria-hidden="true"></i>@endif
        <span>{{ $title }}</span>
        @if (!is_null($count))<span class="fold-count">{{ $count }}</span>@endif
        <i class="bi bi-chevron-down fold-chevron" aria-hidden="true"></i>
    </summary>
    <div>{{ $slot }}</div>
</details>
