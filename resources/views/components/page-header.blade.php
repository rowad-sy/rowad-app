{{-- عنوان الصفحة + وصف + مسار + إجراء أساسي (الـ slot) --}}
@props(['title', 'description' => null, 'breadcrumb' => []])

<div class="page-header-row">
    <div style="min-width:0">
        @if (!empty($breadcrumb))
            <x-breadcrumb :items="$breadcrumb" />
        @endif
        <h1 class="page-title">{{ $title }}</h1>
        @if ($description)
            <p class="page-desc">{{ $description }}</p>
        @endif
        {{ $meta ?? '' }}
    </div>
    @if (!$slot->isEmpty())
        <div class="page-actions">{{ $slot }}</div>
    @endif
</div>
