{{-- $hasTerm: did the customer type anything? The rest: see _listing. --}}
<x-layouts.storefront :title="$hasTerm ? 'Search: '.$filter->search : 'Search'" description="Search our whole menu." :noindex="true">
    <div class="page-container py-8">
        <x-breadcrumbs :items="[['label' => 'Home', 'url' => route('home')], ['label' => 'Search']]" />
        <h1 class="mt-4 font-display text-3xl font-bold md:text-4xl">{{ $hasTerm ? 'Results for "'.$filter->search.'"' : 'Search the menu' }}</h1>

        <x-storefront.search-form id="page-search" class="mt-5 max-w-xl" :value="$filter->search" />

        @if ($hasTerm)
            @include('catalog::storefront._listing')
        @else
            <x-empty-state class="mt-6" title="What are you hungry for?" message="Type the name of a dish or an ingredient." icon="search">
                <x-slot:action>
                    <x-link-button :href="route('menu.index')">Browse the menu</x-link-button>
                </x-slot:action>
            </x-empty-state>
        @endif
    </div>
</x-layouts.storefront>
