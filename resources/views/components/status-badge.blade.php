{{-- شارة حالة بألوان ثابتة: success|warning|danger|info|brand|neutral --}}
@props(['tone' => 'neutral'])

<span {{ $attributes->class(['badge-status', 'is-'.$tone => $tone !== 'neutral']) }}>{{ $slot }}</span>
