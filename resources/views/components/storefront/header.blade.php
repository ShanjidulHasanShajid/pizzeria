@props(['siteName', 'logoUrl' => null])
@php
    $cartCount = 3; // fake until the cart exists (Phase 11)
@endphp
<header class="border-b border-line bg-white">
    <div class="page-container flex items-center gap-3 py-3 md:gap-6">
        <button type="button" class="-ml-2 p-2 text-ink lg:hidden" aria-label="Open menu" x-on:click="drawer = true">
            <x-icon name="bars-3" class="h-6 w-6" />
        </button>

        <x-storefront.logo :site-name="$siteName" :logo-url="$logoUrl" name-class="text-xl text-brand-700 md:text-2xl" />

        <x-storefront.search-form id="site-search" class="mx-auto hidden max-w-xl flex-1 md:block" :value="is_string(request()->query('q')) ? request()->query('q') : ''" />

        <div class="ml-auto flex items-center gap-1 md:ml-0">
            @auth
                <a href="{{ route('account.dashboard') }}" class="hidden items-center gap-2 rounded-btn px-3 py-2 text-sm font-semibold hover:bg-brand-50 md:inline-flex">
                    <x-icon name="user" class="h-5 w-5" /> {{ Str::of(auth()->user()->name)->before(' ') }}
                </a>
            @else
                <a href="{{ route('login') }}" class="hidden items-center gap-2 rounded-btn px-3 py-2 text-sm font-semibold hover:bg-brand-50 md:inline-flex">
                    <x-icon name="user" class="h-5 w-5" /> Sign in
                </a>
            @endauth
            <a href="{{ route('cart.show') }}" class="relative rounded-btn p-2 hover:bg-brand-50" aria-label="Cart, {{ $cartCount }} items">
                <x-icon name="shopping-bag" class="h-6 w-6" />
                <span class="absolute -right-0.5 -top-0.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-brand-600 px-1 text-xs font-bold text-white" aria-hidden="true">{{ $cartCount }}</span>
            </a>
        </div>
    </div>
</header>
