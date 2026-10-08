<x-layouts.storefront title="My account">
    <div class="page-container py-10">
        <x-breadcrumbs :items="[['label' => 'Home', 'url' => route('home')], ['label' => 'My account']]" />
        <h1 class="mt-2 font-display text-3xl font-bold">Hello, {{ auth()->user()->name }}</h1>

        <x-card class="mt-6 max-w-xl">
            <p class="text-sm text-muted">Your orders, addresses and saved pizzas will appear here. This page is a placeholder until Phase 14.</p>
            <dl class="mt-4 space-y-1 text-sm">
                <div><dt class="inline font-medium">Email:</dt> <dd class="inline">{{ auth()->user()->email }}</dd></div>
                <div><dt class="inline font-medium">Phone:</dt> <dd class="inline">{{ auth()->user()->phone ?? '-' }}</dd></div>
            </dl>
            <form method="POST" action="{{ route('logout') }}" class="mt-6">
                @csrf
                <x-button type="submit" variant="outline">Sign out</x-button>
            </form>
        </x-card>
    </div>
</x-layouts.storefront>
