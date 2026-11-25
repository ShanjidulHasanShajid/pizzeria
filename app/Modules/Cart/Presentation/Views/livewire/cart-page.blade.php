{{-- $cart: PricedCart. $images: line id => address. $problem, $status: what the last action said, or null. --}}
@use('App\Modules\Cart\Domain\Enums\CartLineStatus')
@use('App\Modules\Cart\Domain\ValueObjects\KitchenNote')
@use('App\Modules\Shared\Domain\ValueObjects\Quantity')
<div>
    @if ($problem !== null)
        <x-alert type="error" class="mb-4">{{ $problem }}</x-alert>
    @elseif ($status !== null)
        <x-alert type="success" class="mb-4">{{ $status }}</x-alert>
    @endif

    @if ($cart->isEmpty())
        <x-empty-state title="Your cart is empty" message="Add something delicious from the menu." icon="shopping-bag">
            <x-slot:action>
                <x-link-button :href="route('menu.index')">Browse the menu</x-link-button>
            </x-slot:action>
        </x-empty-state>
    @else
        @if (! $cart->canCheckOut())
            <x-alert type="warning" class="mb-4">Some items cannot be ordered right now. Please remove them to continue.</x-alert>
        @endif
        @if ($cart->hasChangedPrices())
            <x-alert type="info" class="mb-4">
                Some prices have changed since you added the items. The cart already shows the new prices.
                <div class="mt-2"><x-button size="sm" variant="outline" wire:click="acceptPrices">OK, got it</x-button></div>
            </x-alert>
        @endif

        <div class="grid items-start gap-8 lg:grid-cols-3">
            <ul class="divide-y divide-line rounded-card border border-line bg-white lg:col-span-2">
                @foreach ($cart->lines as $line)
                    @php
                        $isUnavailable = $line->status === CartLineStatus::Unavailable;
                    @endphp
                    <li wire:key="line-{{ $line->lineId }}" class="flex gap-4 p-4">
                        <img src="{{ $images[$line->lineId] }}" alt="" class="h-20 w-20 shrink-0 rounded-lg bg-cream object-cover {{ $isUnavailable ? 'opacity-50 grayscale' : '' }}">

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-baseline justify-between gap-x-4">
                                <h2 class="font-semibold text-ink">
                                    @if ($line->productSlug !== null)
                                        <a href="{{ route('products.show', $line->productSlug) }}" class="hover:text-brand-700">{{ $line->productName }}</a>
                                    @else
                                        {{ $line->productName }}
                                    @endif
                                </h2>
                                @if ($line->lineTotal !== null)
                                    <p class="font-semibold tabular-nums">@money($line->lineTotal)</p>
                                @endif
                            </div>

                            @if ($line->variantLabel !== null)
                                <p class="text-sm text-muted">{{ $line->variantLabel }}, @money($line->unitPrice) each</p>
                            @endif
                            @if ($line->options !== [])
                                <ul class="mt-1 text-sm text-muted">
                                    @foreach ($line->options as $option)
                                        <li wire:key="line-{{ $line->lineId }}-option-{{ $loop->index }}">
                                            {{ $option->groupTitle }}: {{ $option->name }}
                                            @if ($option->charged->isZero())
                                                (free)
                                            @else
                                                (+@money($option->charged))
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @endif

                            @if ($isUnavailable)
                                <p class="mt-2 text-sm font-medium text-red-700">{{ $line->problem }}</p>
                            @elseif ($line->status === CartLineStatus::PriceChanged)
                                <p class="mt-2 text-sm font-medium text-amber-800">The price changed from @money($line->lastUnitPrice) to @money($line->unitPrice).</p>
                            @endif

                            <div class="mt-3 flex flex-wrap items-center gap-4">
                                @if ($isUnavailable)
                                    <span class="text-sm text-muted">Quantity {{ $line->quantity }}</span>
                                @else
                                    <div class="flex items-center gap-2" role="group" aria-label="Quantity of {{ $line->productName }}">
                                        <x-button size="sm" variant="outline" wire:click="setQuantity({{ $line->lineId }}, {{ $line->quantity - 1 }})" aria-label="One less" :disabled="$line->quantity <= Quantity::MIN"><x-icon name="minus" class="h-4 w-4" /></x-button>
                                        <span class="w-8 text-center font-semibold tabular-nums" aria-live="polite">{{ $line->quantity }}</span>
                                        <x-button size="sm" variant="outline" wire:click="setQuantity({{ $line->lineId }}, {{ $line->quantity + 1 }})" aria-label="One more" :disabled="$line->quantity >= Quantity::MAX"><x-icon name="plus" class="h-4 w-4" /></x-button>
                                    </div>
                                @endif
                                <button type="button" wire:click="remove({{ $line->lineId }})" class="inline-flex items-center gap-1 text-sm font-medium text-muted hover:text-red-700" aria-label="Remove {{ $line->productName }}">
                                    <x-icon name="trash" class="h-4 w-4" /> Remove
                                </button>
                            </div>

                            @if (! $isUnavailable)
                                <form wire:submit="saveNote({{ $line->lineId }})" class="mt-3 flex items-end gap-2">
                                    <div class="flex-1">
                                        <label for="note-{{ $line->lineId }}" class="mb-1 block text-sm font-medium text-ink">Note for the kitchen</label>
                                        <textarea id="note-{{ $line->lineId }}" rows="1" maxlength="{{ KitchenNote::MAX_LENGTH }}" wire:model="notes.{{ $line->lineId }}" class="field text-sm"></textarea>
                                    </div>
                                    <x-button type="submit" size="sm" variant="outline">Save note</x-button>
                                </form>
                            @elseif ($line->note !== null)
                                <p class="mt-2 whitespace-pre-line text-sm text-muted">Note: {{ $line->note }}</p>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>

            <section class="rounded-card border border-line bg-white p-4 lg:sticky lg:top-4" aria-labelledby="cart-summary">
                <h2 id="cart-summary" class="font-display text-xl font-bold">Summary</h2>
                <p class="mt-3 flex items-baseline justify-between" aria-live="polite" wire:loading.class="opacity-50">
                    <span class="font-semibold">Subtotal</span>
                    <span class="font-display text-2xl font-bold tabular-nums">@money($cart->subtotal)</span>
                </p>
                <p class="mt-1 text-sm text-muted">VAT and SD included. The delivery fee is added at checkout.</p>

                @if ($cart->canCheckOut())
                    <x-link-button :href="route('checkout.show')" size="lg" class="mt-4 w-full">Proceed to checkout</x-link-button>
                @else
                    <x-button size="lg" class="mt-4 w-full" disabled>Proceed to checkout</x-button>
                @endif
                <x-link-button :href="route('menu.index')" variant="outline" class="mt-2 w-full">Keep shopping</x-link-button>
                <button type="button" wire:click="clear" wire:confirm="Remove everything from your cart?" class="mt-4 w-full text-center text-sm text-muted hover:text-red-700">Empty the cart</button>
            </section>
        </div>
    @endif
</div>
