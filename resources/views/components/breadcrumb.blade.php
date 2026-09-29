{{-- مسار الصفحة: items = [['label' => '...', 'url' => '...'|null], ...]؛ العنصر الأخير هو الصفحة الحالية --}}
@props(['items' => []])

<nav aria-label="مسار الصفحة" class="page-breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.portal') }}">الرئيسية</a></li>
        @foreach ($items as $item)
            @if ($loop->last || empty($item['url']))
                <li class="breadcrumb-item active" @if ($loop->last) aria-current="page" @endif>{{ $item['label'] }}</li>
            @else
                <li class="breadcrumb-item"><a href="{{ $item['url'] }}">{{ $item['label'] }}</a></li>
            @endif
        @endforeach
    </ol>
</nav>
