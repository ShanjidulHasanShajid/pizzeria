{{-- $count: items in the cart. $placement: header, or bar (the bar at the bottom of small screens). --}}
@php
    $label = $count === 0 ? 'Cart, empty' : 'Cart, '.$count.' '.Str::plural('item', $count);
    $shown = $count > 99 ? '99+' : (string) $count;
@endphp
<div class="contents">
@if ($placement === 'bar')
    <a href="{{ route('cart.show') }}" class="relative flex flex-col items-center gap-0.5 py-2 text-xs font-medium text-muted hover:text-brand-700" aria-label="{{ $label }}">
        <span class="relative">
            <x-icon name="shopping-bag" class="h-6 w-6" />
            @if ($count > 0)
                <span class="absolute -right-2 -top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-brand-600 px-1 text-[10px] font-bold text-white" aria-hidden="true">{{ $shown }}</span>
            @endif
        </span>
        Cart
    </a>
@else
    <a href="{{ route('cart.show') }}" class="relative rounded-btn p-2 hover:bg-brand-50" aria-label="{{ $label }}">
        <x-icon name="shopping-bag" class="h-6 w-6" />
        @if ($count > 0)
            <span class="absolute -right-0.5 -top-0.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-brand-600 px-1 text-xs font-bold text-white" aria-hidden="true">{{ $shown }}</span>
        @endif
    </a>
@endif
</div>
