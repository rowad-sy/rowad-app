{{-- مؤشر: شارة الأيقونة بجانب العنوان والرقم، ثم التلميح. tone: brand|success|warning|danger|info|null --}}
@props(['label', 'value', 'href' => null, 'tone' => null, 'icon' => null, 'hint' => null])

@php $tag = $href ? 'a' : 'div'; @endphp
<{!! $tag !!} @if ($href) href="{{ $href }}" @endif class="{{ implode(' ', array_filter(['kpi', $tone ? 'tone-'.$tone : null, $icon ? 'has-icon' : null])) }}">
    @if ($icon)<span class="kpi-icon" aria-hidden="true"><i class="bi {{ $icon }}"></i></span>@endif
    <div class="kpi-body">
        <div class="kpi-label">{{ $label }}</div>
        <div class="kpi-value">{{ $value }}</div>
        @if ($hint)<div class="kpi-hint">{{ $hint }}@if ($href)<i class="bi bi-arrow-left" aria-hidden="true"></i>@endif</div>@endif
    </div>
</{!! $tag !!}>
