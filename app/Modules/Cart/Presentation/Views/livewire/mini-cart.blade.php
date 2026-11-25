{{-- $cart: PricedCart (empty while the drawer is closed). $images: line id => address. $wasAdded, $problem: what just happened. --}}
@use('App\Modules\Cart\Domain\Enums\CartLineStatus')
<div x-data="{ open: $wire.entangle('isOpen') }" x-on:keydown.escape.window="open = false">
    <div x-show="open" x-cloak class="fixed inset-0 z-50" role="dialog" aria-modal="true" aria-labelledby="mini-cart-title">
        <div class="absolute inset-0 bg-ink/60" x-on:click="open = false" aria-hidden="true"></div>

        <div x-show="open" x-trap.noscroll="open"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
            class="absolute right-0 top-0 flex h-full w-96 max-w-[90%] flex-col bg-white shadow-pop">

            <div class="flex items-center justify-between border-b border-line p-4">
                <h2 id="mini-cart-title" class="font-display text-xl font-bold">Your cart</h2>
                <button type="button" x-on:click="open = false" aria-label="Close cart" class="p-2 text-muted hover:text-ink">
                    <x-icon name="x-mark" class="h-6 w-6" />
                </button>
            </div>

            <div class="flex-1 overflow-y-auto p-4">
                @if ($problem !== null)
                    <x-alert type="error" class="mb-4">{{ $problem }}</x-alert>
                @elseif ($wasAdded)
                    <x-alert type="success" class="mb-4">Added to your cart.</x-alert>
                @endif

                <ul class="divide-y divide-line">
                    @foreach ($cart->lines as $line)
                        <li wire:key="mini-{{ $line->lineId }}" class="flex gap-3 py-3">
                            <img src="{{ $images[$line->lineId] }}" alt="" class="h-14 w-14 rounded-lg bg-cream object-cover">
                            <div class="min-w-0 flex-1 text-sm">
                                <p class="font-semibold text-ink">{{ $line->productName }}</p>
                                <p class="text-muted">
                                    {{ $line->quantity }} × {{ $line->variantLabel ?? 'not available' }}@foreach ($line->options as $option), {{ $option->name }}@endforeach
                                </p>
                                @if ($line->status === CartLineStatus::Unavailable)
                                    <p class="font-medium text-red-700">{{ $line->problem }}</p>
                                @endif
                            </div>
                            @if ($line->lineTotal !== null)
                                <p class="text-sm font-semibold tabular-nums">@money($line->lineTotal)</p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="border-t border-line p-4">
                <p class="flex items-baseline justify-between">
                    <span class="font-semibold">Subtotal</span>
                    <span class="font-display text-xl font-bold tabular-nums">@money($cart->subtotal)</span>
                </p>
                <x-link-button :href="route('cart.show')" size="lg" class="mt-3 w-full">View cart</x-link-button>
                <x-button variant="outline" class="mt-2 w-full" x-on:click="open = false">Keep shopping</x-button>
            </div>
        </div>
    </div>
</div>
