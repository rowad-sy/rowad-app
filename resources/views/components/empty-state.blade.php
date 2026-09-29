@props(['icon' => 'bi-inbox', 'title' => 'لا توجد بيانات', 'hint' => null])

<div class="empty-state">
    <i class="bi {{ $icon }}" aria-hidden="true"></i>
    <div class="empty-title">{{ $title }}</div>
    @if ($hint)<div class="small">{{ $hint }}</div>@endif
    {{ $slot }}
</div>
