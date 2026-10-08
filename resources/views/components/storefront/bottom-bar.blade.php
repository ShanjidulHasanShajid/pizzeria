@php
    $cartCount = 3; // fake until Phase 11
    $itemClass = 'flex flex-col items-center gap-0.5 py-2 text-xs font-medium text-muted hover:text-brand-700';
@endphp
<nav class="fixed inset-x-0 bottom-0 z-40 border-t border-line bg-white lg:hidden" aria-label="Quick links">
    <ul class="grid grid-cols-5">
        <li>
            <a href="{{ route('home') }}" class="{{ $itemClass }}" @if (request()->routeIs('home')) aria-current="page" @endif>
                <x-icon name="home" class="h-6 w-6" /> Home
            </a>
        </li>
        <li>
            <button type="button" class="{{ $itemClass }} w-full" x-on:click="drawer = true">
                <x-icon name="bars-3" class="h-6 w-6" /> Menu
            </button>
        </li>
        <li>
            <a href="{{ auth()->check() ? route('account.dashboard') : route('login') }}" class="{{ $itemClass }}">
                <x-icon name="user" class="h-6 w-6" /> Account
            </a>
        </li>
        <li>
            <a href="{{ route('cart.show') }}" class="{{ $itemClass }} relative" aria-label="Cart, {{ $cartCount }} items">
                <span class="relative">
                    <x-icon name="shopping-bag" class="h-6 w-6" />
                    <span class="absolute -right-2 -top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-brand-600 px-1 text-[10px] font-bold text-white" aria-hidden="true">{{ $cartCount }}</span>
                </span>
                Cart
            </a>
        </li>
        <li>
            <a href="{{ route('search') }}" class="{{ $itemClass }}">
                <x-icon name="search" class="h-6 w-6" /> Search
            </a>
        </li>
    </ul>
</nav>

