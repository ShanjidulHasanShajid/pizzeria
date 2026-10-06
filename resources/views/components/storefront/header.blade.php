@php
    $cartCount = 3; // fake until the cart exists (Phase 11)
@endphp
<header class="border-b border-line bg-white">
    <div class="page-container flex items-center gap-3 py-3 md:gap-6">
        <button type="button" class="-ml-2 p-2 text-ink lg:hidden" aria-label="Open menu" x-on:click="drawer = true">
            <x-icon name="bars-3" class="h-6 w-6" />
        </button>

        <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2">
            <img src="{{ asset('images/logo-mark.svg') }}" alt="" class="h-9 w-9 md:h-10 md:w-10">
            <span class="font-display text-xl font-bold text-brand-700 md:text-2xl">{{ config('app.name') }}</span>
        </a>

        <form action="{{ route('search') }}" method="GET" role="search" class="relative mx-auto hidden max-w-xl flex-1 md:block">
            <label for="site-search" class="sr-only">Search the menu</label>
            <input id="site-search" type="search" name="q" value="{{ request('q') }}" placeholder="Search pizza, pasta, desserts..." class="field pr-12">
            <button type="submit" class="absolute inset-y-0 right-0 px-3 text-muted hover:text-brand-700" aria-label="Search">
                <x-icon name="search" class="h-5 w-5" />
            </button>
        </form>

        <div class="ml-auto flex items-center gap-1 md:ml-0">
            <a href="{{ url('/login') }}" class="hidden items-center gap-2 rounded-btn px-3 py-2 text-sm font-semibold hover:bg-brand-50 md:inline-flex">
                <x-icon name="user" class="h-5 w-5" /> Account
            </a>
            <a href="{{ route('cart.show') }}" class="relative rounded-btn p-2 hover:bg-brand-50" aria-label="Cart, {{ $cartCount }} items">
                <x-icon name="shopping-bag" class="h-6 w-6" />
                <span class="absolute -right-0.5 -top-0.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-brand-600 px-1 text-xs font-bold text-white" aria-hidden="true">{{ $cartCount }}</span>
            </a>
        </div>
    </div>
</header>