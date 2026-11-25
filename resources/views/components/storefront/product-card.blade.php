{{-- $product: a ProductCard, or an array of the same shape as ProductCards::toArray() (UI kit). --}}
@props(['product'])
@inject('cards', 'App\Modules\Catalog\Presentation\Support\ProductCards')
@php
    $product = is_array($product) ? $product : $cards->toArray($product);
    $state = $product['state']; // add | options | combo | soldout
    $soldOut = $state === 'soldout';
    $url = route('products.show', $product['slug']);
@endphp
<article class="group flex h-full flex-col overflow-hidden rounded-card border border-line bg-white shadow-card">
    <a href="{{ $url }}" class="relative block aspect-[4/3] overflow-hidden bg-cream" tabindex="-1" aria-hidden="true">
        <img src="{{ $product['image'] }}" alt="" loading="lazy"
            class="h-full w-full object-cover transition duration-300 group-hover:scale-105 {{ $soldOut ? 'opacity-50 grayscale' : '' }}">
        @if ($soldOut || $product['badges'] !== [])
            <div class="absolute left-2 top-2 flex flex-wrap gap-1">
                @if ($soldOut)
                    <x-badge variant="soldout">Sold out</x-badge>
                @endif
                @foreach ($product['badges'] as $badge)
                    <x-badge :variant="$badge['variant']">{{ $badge['label'] }}</x-badge>
                @endforeach
            </div>
        @endif
    </a>

    <div class="flex flex-1 flex-col p-4">
        <h3 class="font-semibold text-ink"><a href="{{ $url }}" class="hover:text-brand-700">{{ $product['name'] }}</a></h3>
        @if ($product['description'] !== '')
            <p class="mt-1 line-clamp-2 text-sm text-muted">{{ $product['description'] }}</p>
        @endif

        @if ($product['tags'] !== [])
            <ul class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-xs font-medium" aria-label="Tags">
                @foreach ($product['tags'] as $tag)
                    <li class="inline-flex items-center gap-1 {{ $tag['class'] }}">
                        @if ($tag['icon'])
                            <x-icon :name="$tag['icon']" class="h-3.5 w-3.5" />
                        @endif
                        {{ $tag['label'] }}
                    </li>
                @endforeach
            </ul>
        @endif

        @if ($product['sizes'] !== [])
            <ul class="mt-2 flex flex-wrap gap-1 text-xs text-muted" aria-label="Sizes">
                @foreach ($product['sizes'] as $size)
                    <li class="rounded-full border border-line px-2 py-0.5">{{ $size }}</li>
                @endforeach
            </ul>
        @endif

        <div class="mt-auto flex items-center justify-between gap-2 pt-4">
            <x-money :amount="$product['price']" :from="$product['isFromPrice']" />

            @switch($state)
                @case('add')
                    {{-- The mini cart listens (MiniCart::add). Throttled, so that a double click adds one. --}}
                    <x-button size="sm" x-on:click.throttle.1000ms="$dispatch('add-to-cart', { productId: {{ $product['id'] }}, variantId: {{ $product['variantId'] }} })" :aria-label="'Add '.$product['name'].' to cart'">Add to cart</x-button>
                    @break
                @case('options')
                    <x-link-button :href="$url" variant="outline" size="sm">Choose options</x-link-button>
                    @break
                @case('combo')
                    <x-link-button :href="$url" variant="secondary" size="sm">Customize combo</x-link-button>
                    @break
                @default
                    <x-button size="sm" variant="outline" disabled>Sold out</x-button>
            @endswitch
        </div>
    </div>
</article>
