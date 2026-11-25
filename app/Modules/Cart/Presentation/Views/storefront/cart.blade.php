<x-layouts.storefront title="Your cart" :noindex="true">
    <div class="page-container py-8">
        <x-breadcrumbs :items="[['label' => 'Home', 'url' => route('home')], ['label' => 'Cart']]" />
        <h1 class="mt-4 font-display text-3xl font-bold md:text-4xl">Your cart</h1>

        <div class="mt-6">
            <livewire:cart.cart-page />
        </div>
    </div>
</x-layouts.storefront>
