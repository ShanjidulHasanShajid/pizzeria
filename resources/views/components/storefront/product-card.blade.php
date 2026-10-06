@props(['product'])
@php
    $state = $product['state']; // simple | options | combo | soldout
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
                    <x-badge :variant="$badge">{{ ucfirst($badge) }}</x-badge>
                @endforeach
            </div>
        @endif
    </a>

    <div class="flex flex-1 flex-col p-4">
        <h3 class="font-semibold text-ink"><a href="{{ $url }}" class="hover:text-brand-700">{{ $product['name'] }}</a></h3>
        <p class="mt-1 line-clamp-2 text-sm text-muted">{{ $product['description'] }}</p>

        <div class="mt-auto flex items-center justify-between gap-2 pt-4">
            <x-money :amount="$product['price']" :from="in_array($state, ['options', 'combo'], true)" />

            @switch($state)
                @case('simple')
                    <x-button size="sm" aria-label="Add {{ $product['name'] }} to cart">Add to cart</x-button>
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