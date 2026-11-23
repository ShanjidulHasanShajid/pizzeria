{{--
    $product: ProductDetail. $category: CategoryPage (for the path) or null. $images: full, thumbnail, isMain. $mainImage: address or null.
    $related: list<ProductCard>.
--}}
@use('App\Modules\Catalog\Domain\Enums\ProductType')
@use('App\Modules\Catalog\Domain\Enums\TagType')
@php
    $crumbs = [['label' => 'Home', 'url' => route('home')], ['label' => 'Menu', 'url' => route('menu.index')]];

    if ($category?->parentSlug !== null) {
        $crumbs[] = ['label' => $category->parentName, 'url' => route('categories.show', $category->parentSlug)];
    }

    if ($category !== null) {
        $crumbs[] = ['label' => $category->name, 'url' => $category->parentSlug === null ? route('categories.show', $category->slug) : route('categories.sub', [$category->parentSlug, $category->slug])];
    }

    $crumbs[] = ['label' => $product->name];
    $main = $mainImage ?? asset('images/placeholder.svg');
    $start = $product->startVariant();
@endphp
<x-layouts.storefront :title="$product->name.' - '.$product->categoryName" :description="Str::limit($product->shortDescription ?: $product->name.' from our '.$product->categoryName.' menu. Order online.', 160)" :image="$mainImage">
    <div class="page-container py-8">
        <x-breadcrumbs :items="$crumbs" />

        <div class="mt-6 grid gap-8 lg:grid-cols-2">
            <div x-data="{ shown: @js($main) }">
                <div class="aspect-square overflow-hidden rounded-card border border-line bg-cream">
                    <img src="{{ $main }}" x-bind:src="shown" alt="{{ $product->name }}" class="h-full w-full object-cover {{ $product->isAvailable ? '' : 'opacity-50 grayscale' }}">
                </div>
                @if (count($images) > 1)
                    <ul class="mt-3 flex flex-wrap gap-2" aria-label="Photos">
                        @foreach ($images as $image)
                            <li>
                                <button type="button" x-on:click="shown = @js($image['full'])" x-bind:aria-pressed="(shown === @js($image['full'])).toString()" aria-label="Show photo {{ $loop->iteration }}"
                                    class="block h-20 w-20 overflow-hidden rounded-lg border-2" x-bind:class="shown === @js($image['full']) ? 'border-brand-600' : 'border-line'">
                                    <img src="{{ $image['thumbnail'] }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div>
                <div class="flex flex-wrap gap-1">
                    @if (! $product->isAvailable)
                        <x-badge variant="soldout">Sold out</x-badge>
                    @endif
                    @if ($product->isNew)
                        <x-badge variant="new">New</x-badge>
                    @endif
                    @if ($product->isHot)
                        <x-badge variant="hot">Hot</x-badge>
                    @endif
                </div>
                <h1 class="mt-2 font-display text-3xl font-bold md:text-4xl">{{ $product->name }}</h1>
                @if ($product->shortDescription)
                    <p class="mt-2 text-lg text-muted">{{ $product->shortDescription }}</p>
                @endif

                @if ($product->tags !== [])
                    <ul class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-sm font-medium" aria-label="Tags">
                        @foreach ($product->tags as $tag)
                            <li>
                                <a href="{{ route('tags.show', $tag->slug) }}" class="inline-flex items-center gap-1 hover:underline {{ $tag->type === TagType::Allergen ? 'text-amber-800' : 'text-accent-700' }}">
                                    @if ($tag->icon)
                                        <x-icon :name="$tag->icon" class="h-4 w-4" />
                                    @endif
                                    {{ $tag->name }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif

                <p class="mt-3 flex items-center gap-2 text-sm text-muted"><x-icon name="clock" class="h-4 w-4" /> {{ $product->leadTimeLabel }}</p>

                <div class="mt-6">
                    @if (! $product->isAvailable)
                        <p class="font-display text-2xl font-bold text-ink">@money($start->price)</p>
                        <x-alert type="warning" class="mt-3">This item is sold out right now. Please come back later.</x-alert>
                        <x-button size="lg" class="mt-3 w-full" disabled>Sold out</x-button>
                    @elseif ($product->type === ProductType::Builder)
                        <p class="font-display text-2xl font-bold text-ink"><span class="text-sm font-normal text-muted">From</span> @money($start->price)</p>
                        <x-link-button :href="route('builder.show')" size="lg" class="mt-3">Start building</x-link-button>
                    @elseif ($product->type === ProductType::Combo)
                        <p class="font-display text-2xl font-bold text-ink">@money($start->price)</p>
                        <x-button size="lg" class="mt-3 w-full" disabled>Customize combo</x-button>
                        <p class="mt-1 text-center text-xs text-muted">Meal deals open soon.</p>
                    @else
                        <livewire:customization.product-configurator :product-id="$product->id" :variant-id="$start->id" />
                    @endif
                </div>
            </div>
        </div>

        @if ($product->description)
            <section class="mt-10 max-w-3xl" aria-labelledby="about-product">
                <h2 id="about-product" class="font-display text-2xl font-bold text-ink">About this item</h2>
                <p class="mt-3 whitespace-pre-line text-muted">{{ $product->description }}</p>
            </section>
        @endif

        @if ($related !== [])
            <section class="mt-12" aria-label="You may also like">
                <x-storefront.section-heading title="You may also like" />
                <ul class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($related as $card)
                        <li><x-storefront.product-card :product="$card" /></li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
</x-layouts.storefront>
