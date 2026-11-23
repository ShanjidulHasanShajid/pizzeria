<x-layouts.storefront :title="$title" :noindex="true">
    <div class="page-container py-8">
        <x-breadcrumbs :items="[['label' => 'Home', 'url' => route('home')], ['label' => $title]]" />
        <h1 class="mt-4 font-display text-3xl font-bold md:text-4xl">{{ $title }}</h1>

        <x-empty-state class="mt-6" :title="$title.' is coming soon'" :message="'This page is a placeholder. It is built in Phase '.($phase ?? '?').'.'">
            <x-slot:action>
                <x-link-button :href="route('menu.index')">Browse the menu</x-link-button>
            </x-slot:action>
        </x-empty-state>
    </div>
</x-layouts.storefront>
