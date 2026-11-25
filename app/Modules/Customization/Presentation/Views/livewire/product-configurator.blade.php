{{--
    $configuration: ProductConfiguration, or null when the product left the menu. $variant: the chosen size or null.
    $choices: group id => option ids. $line: PricedLineView, or null when the choice is refused ($problem says why).
    $cartProblem: why the cart refused the item, or null.
--}}
@use('App\Modules\Customization\Presentation\Support\OptionGroupHint')
@use('App\Modules\Shared\Domain\ValueObjects\Quantity')
<div>
    @if ($configuration === null)
        <x-alert type="warning">This item is not on the menu any more.</x-alert>
    @else
        <div class="space-y-6">
            @if (count($configuration->variants) > 1)
                <fieldset>
                    <legend class="text-base font-semibold text-ink">Size</legend>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach ($configuration->variants as $size)
                            <label wire:key="size-{{ $size->id }}" class="relative {{ $size->isAvailable ? 'cursor-pointer' : 'cursor-not-allowed opacity-50' }}">
                                <input type="radio" name="variantId" value="{{ $size->id }}" wire:model.live="variantId" @disabled(! $size->isAvailable) class="peer sr-only">
                                <span class="block rounded-btn border border-line bg-white px-4 py-2 text-sm peer-checked:border-brand-600 peer-checked:bg-brand-50 peer-checked:font-semibold peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-brand-600">
                                    {{ $size->label }} <span class="text-muted">@money($size->price)</span>
                                    @if (! $size->isAvailable)
                                        <span class="text-muted">(sold out)</span>
                                    @endif
                                </span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            @endif

            @foreach ($configuration->groups as $group)
                @php
                    $id = $group->optionGroupId;
                    $picked = $choices[$id] ?? [];
                    $isSingle = $group->maximum === 1;
                    $isFull = ! $isSingle && count($picked) >= $group->maximum;
                    $hasProblem = $problemGroupId === $id;
                @endphp
                <fieldset wire:key="group-{{ $id }}" @if ($hasProblem) aria-describedby="problem-{{ $id }}" @endif>
                    <legend class="text-base font-semibold text-ink">{{ $group->title }}</legend>
                    <p class="text-sm text-muted">{{ OptionGroupHint::for($group) }}</p>
                    @if ($group->description)
                        <p class="text-sm text-muted">{{ $group->description }}</p>
                    @endif

                    <div class="mt-2 divide-y divide-line rounded-card border border-line bg-white">
                        @if ($isSingle && ! $group->isRequired)
                            <label wire:key="none-{{ $id }}" class="flex cursor-pointer items-center gap-3 px-4 py-2.5 text-sm">
                                <input type="radio" name="group-{{ $id }}" value="" wire:model.live="single.{{ $id }}" class="h-4 w-4 border-stone-400 text-brand-600 focus:ring-brand-600">
                                <span class="flex-1 text-muted">None</span>
                            </label>
                        @endif
                        @foreach ($group->options as $option)
                            @php
                                $price = $option->priceFor($variant?->sizeTierId);
                                $isPicked = in_array($option->id, $picked, true);
                                $isOff = ! $option->isAvailable || $price === null || ($isFull && ! $isPicked);
                            @endphp
                            <label wire:key="option-{{ $option->id }}" class="flex items-center gap-3 px-4 py-2.5 text-sm {{ $isOff ? 'cursor-not-allowed opacity-50' : 'cursor-pointer' }}">
                                @if ($isSingle)
                                    <input type="radio" name="group-{{ $id }}" value="{{ $option->id }}" wire:model.live="single.{{ $id }}" @disabled($isOff) class="h-4 w-4 border-stone-400 text-brand-600 focus:ring-brand-600">
                                @else
                                    <input type="checkbox" value="{{ $option->id }}" wire:model.live="multiple.{{ $id }}" @disabled($isOff && ! $isPicked) class="h-4 w-4 rounded border-stone-400 text-brand-600 focus:ring-brand-600">
                                @endif
                                <span class="flex-1">
                                    {{ $option->name }}
                                    @if ($option->isVeg)
                                        <x-badge variant="veg">Veg</x-badge>
                                    @endif
                                </span>
                                <span class="text-muted">
                                    @if (! $option->isAvailable)
                                        Sold out
                                    @elseif ($price === null)
                                        Not for this size
                                    @elseif ($price->isZero())
                                        Free
                                    @else
                                        +@money($price)
                                    @endif
                                </span>
                            </label>
                        @endforeach
                    </div>

                    @if ($hasProblem)
                        <p id="problem-{{ $id }}" class="mt-1 text-sm font-medium text-amber-800" role="status">{{ $problem }}</p>
                    @endif
                </fieldset>
            @endforeach

            <div>
                <label for="kitchen-note" class="mb-1 block text-sm font-medium text-ink">Note for the kitchen</label>
                <textarea id="kitchen-note" rows="2" maxlength="200" wire:model="note" class="field" placeholder="For example: less spicy, please"></textarea>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-4 rounded-card border border-line bg-white p-4">
                <div class="flex items-center gap-2" role="group" aria-label="Quantity">
                    <x-button size="sm" variant="outline" wire:click="less" aria-label="One less" :disabled="$quantity <= Quantity::MIN"><x-icon name="minus" class="h-4 w-4" /></x-button>
                    <span class="w-8 text-center font-semibold tabular-nums" aria-live="polite">{{ $quantity }}</span>
                    <x-button size="sm" variant="outline" wire:click="more" aria-label="One more" :disabled="$quantity >= Quantity::MAX"><x-icon name="plus" class="h-4 w-4" /></x-button>
                </div>

                <div class="text-right" aria-live="polite" wire:loading.class="opacity-50">
                    @if ($line)
                        <p class="font-display text-2xl font-bold text-ink">@money($line->lineTotal)</p>
                        @if ($line->quantity > 1)
                            <p class="text-sm text-muted">@money($line->unitPrice) each</p>
                        @endif
                    @elseif ($variant)
                        <p class="font-display text-2xl font-bold text-ink"><span class="text-sm font-normal text-muted">From</span> @money($variant->price)</p>
                    @endif
                </div>

                @if ($problem !== null && $problemGroupId === null)
                    <x-alert type="warning" class="w-full">{{ $problem }}</x-alert>
                @endif

                {{-- When the choice itself is refused, $problem says so already. --}}
                @if ($cartProblem !== null && $problem === null)
                    <x-alert type="error" class="w-full">{{ $cartProblem }}</x-alert>
                @endif

                <x-button size="lg" class="w-full" wire:click="addToCart" wire:loading.attr="disabled" wire:target="addToCart" :disabled="$line === null">Add to cart</x-button>
            </div>

            @if ($line && $line->options !== [])
                <div>
                    <h3 class="text-sm font-semibold text-ink">Your choice</h3>
                    <ul class="mt-1 space-y-0.5 text-sm text-muted">
                        @foreach ($line->options as $option)
                            <li wire:key="summary-{{ $option->optionId }}" class="flex justify-between gap-4">
                                <span>{{ $option->groupTitle }}: {{ $option->optionName }}</span>
                                <span>
                                    @if ($option->isFreePick)
                                        <s>@money($option->price)</s> Free
                                    @elseif ($option->charged->isZero())
                                        Free
                                    @else
                                        +@money($option->charged)
                                    @endif
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @endif
</div>
