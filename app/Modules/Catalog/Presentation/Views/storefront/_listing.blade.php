{{-- $products: paginator of ProductCard. $filter: MenuFilter. $filterTags: list<TagBadge>. $pageUrl: this page without a filter. --}}
@include('catalog::storefront._filters')

@if ($products->total() === 0)
    @if ($filter->isActive())
        <x-empty-state class="mt-6" title="Nothing matches your filter" message="Try fewer tags or a wider price range." icon="search">
            <x-slot:action>
                <x-link-button :href="$pageUrl">Clear the filter</x-link-button>
            </x-slot:action>
        </x-empty-state>
    @else
        <x-empty-state class="mt-6" title="Nothing here yet" message="We are preparing something. Have a look at the rest of the menu." icon="shopping-bag">
            <x-slot:action>
                <x-link-button :href="route('menu.index')">Browse the menu</x-link-button>
            </x-slot:action>
        </x-empty-state>
    @endif
@else
    <p class="mt-6 text-sm text-muted" role="status">{{ $products->total() }} {{ Str::plural('item', $products->total()) }}</p>
    <ul class="mt-3 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($products as $product)
            <li><x-storefront.product-card :product="$product" /></li>
        @endforeach
    </ul>
    <div class="mt-8">{{ $products->links() }}</div>
@endif
