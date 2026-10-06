@props(['variant' => 'neutral'])
@php
    $styles = [
        'neutral' => 'bg-stone-100 text-stone-800',
        'new' => 'bg-sky-100 text-sky-900',
        'hot' => 'bg-brand-100 text-brand-800',
        'veg' => 'bg-accent-100 text-accent-800',
        'spicy' => 'bg-orange-100 text-orange-900',
        'lead' => 'bg-amber-100 text-amber-900',
        'soldout' => 'bg-stone-800 text-white',
        // order status colours (used in the admin and in order tracking)
        'pending' => 'bg-amber-100 text-amber-900',
        'confirmed' => 'bg-sky-100 text-sky-900',
        'preparing' => 'bg-orange-100 text-orange-900',
        'ready' => 'bg-accent-100 text-accent-800',
        'delivered' => 'bg-accent-600 text-white',
        'cancelled' => 'bg-red-100 text-red-900',
    ];
@endphp
<span {{ $attributes->class(['inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold', $styles[$variant] ?? $styles['neutral']]) }}>{{ $slot }}</span>