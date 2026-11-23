{{-- $categories: list<CategoryNode>. $sections: list<CategorySection>, only without a filter; with one: see _listing. --}}
<x-layouts.storefront title="Menu" description="Our whole menu: pizza, pies, pasta, burgers, salads, desserts and drinks.">
    <div class="page-container py-8">
        <x-breadcrumbs :items="[['label' => 'Home', 'url' => route('home')], ['label' => 'Menu']]" />
        <h1 class="mt-4 font-display text-3xl font-bold md:text-4xl">Menu</h1>

        @if ($categories !== [])
            <nav class="mt-5 flex gap-2 overflow-x-auto pb-2" aria-label="Categories">
                @foreach ($categories as $category)
                    <a href="{{ route('categories.show', $category->slug) }}" class="whitespace-nowrap rounded-full border border-line bg-white px-4 py-2 text-sm font-semibold hover:border-brand-600 hover:text-brand-700">{{ $category->name }}</a>
                @endforeach
            </nav>
        @endif

        @if ($filter->isActive())
            @include('catalog::storefront._listing')
        @else
            @include('catalog::storefront._filters')

            @forelse ($sections as $section)
                <section class="mt-10 scroll-mt-4" id="{{ $section->slug }}" aria-labelledby="heading-{{ $section->slug }}">
                    <div class="flex items-end justify-between gap-4">
                        <h2 id="heading-{{ $section->slug }}" class="font-display text-2xl font-bold text-ink md:text-3xl">{{ $section->name }}</h2>
                        <a href="{{ route('categories.show', $section->slug) }}" class="inline-flex shrink-0 items-center gap-1 text-sm font-semibold text-brand-700 hover:underline">
                            View all <span class="sr-only">in {{ $section->name }}</span> <x-icon name="arrow-right" class="h-4 w-4" />
                        </a>
                    </div>
                    <ul class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ($section->products as $product)
                            <li><x-storefront.product-card :product="$product" /></li>
                        @endforeach
                    </ul>
                </section>
            @empty
                <x-empty-state class="mt-6" title="The menu is being prepared" message="Please come back soon." icon="shopping-bag" />
            @endforelse
        @endif
    </div>
</x-layouts.storefront>
