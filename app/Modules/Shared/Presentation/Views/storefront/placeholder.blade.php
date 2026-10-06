@inject('placeholder', 'App\Modules\Shared\Presentation\Support\PlaceholderData')
<x-layouts.storefront :title="$title">
    <div class="page-container py-8">
        <x-breadcrumbs :items="[['label' => 'Home', 'url' => route('home')], ['label' => $title]]" />
        <h1 class="mt-4 font-display text-3xl font-bold md:text-4xl">{{ $title }}</h1>

        @if ($showProducts ?? false)
            <ul class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($placeholder->products(8) as $product)
                    <li><x-storefront.product-card :product="$product" /></li>
                @endforeach
            </ul>
        @else
            <x-empty-state class="mt-6" :title="$title.' is coming soon'" :message="'This page is a placeholder. It is built in Phase '.($phase ?? '?').'.'">
                <x-slot:action>
                    <x-link-button :href="route('menu.index')">Browse the menu</x-link-button>
                </x-slot:action>
            </x-empty-state>
        @endif
    </div>
</x-layouts.storefront>