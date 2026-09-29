{{-- مؤشر: الرقم بارزًا ثم العنوان. tone: brand|success|warning|danger|info|null --}}
@props(['label', 'value', 'href' => null, 'tone' => null, 'icon' => null, 'hint' => null])

@php $tag = $href ? 'a' : 'div'; @endphp
<{!! $tag !!} @if ($href) href="{{ $href }}" @endif class="kpi {{ $tone ? 'tone-'.$tone : '' }}">
    <div class="kpi-label">@if ($icon)<i class="bi {{ $icon }}" aria-hidden="true"></i>@endif{{ $label }}</div>
    <div class="kpi-value">{{ $value }}</div>
    @if ($hint)<div class="kpi-hint">{{ $hint }}</div>@endif
</{!! $tag !!}>
