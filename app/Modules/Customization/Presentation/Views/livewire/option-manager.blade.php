{{--
    The view of the OptionManager Livewire component. A Livewire view needs ONE root element.
    $options: list<AdminOptionRow> in display order. $priceModes: PriceMode cases. $imageUrl: path => URL.
--}}
<div class="space-y-6">
    @if ($status !== '')
        <p class="text-sm text-accent-700" role="status">{{ $status }}</p>
    @endif
    @error('ids')
        <x-alert type="error">{{ $message }}</x-alert>
    @enderror

    {{-- The list is always there (also when empty), so admin.js can make it draggable on page load. --}}
    <ul data-sortable aria-label="Options" class="space-y-3" x-data x-on:sorted="$wire.reorder($event.detail.ids)">
        @foreach ($options as $option)
            @php($row = "rows.{$option->id}")
            <li data-id="{{ $option->id }}" wire:key="option-{{ $option->id }}" class="rounded-card border border-line bg-white p-3">
                <div class="grid gap-3 sm:grid-cols-12 sm:items-start">
                    <div class="flex items-center gap-2 sm:col-span-2">
                        <button type="button" data-handle aria-label="Drag {{ $option->name }} to reorder" class="cursor-grab text-muted hover:text-ink">
                            <x-icon name="bars-3" class="h-5 w-5" />
                        </button>
                        <span class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-line bg-cream">
                            @if ($option->imagePath)
                                <img src="{{ $imageUrl($option->imagePath) }}" alt="" class="h-full w-full object-cover">
                            @else
                                <x-icon name="photo" class="h-5 w-5 text-stone-400" />
                            @endif
                        </span>
                    </div>

                    <div class="sm:col-span-3">
                        <label class="mb-1 block text-xs font-medium text-muted" for="option-name-{{ $option->id }}">Name</label>
                        <input id="option-name-{{ $option->id }}" type="text" maxlength="100" class="field" wire:model="{{ $row }}.name">
                        <x-form.error :name="$row.'.name'" />
                        <label class="mt-2 inline-flex items-center gap-2 text-sm">
                            <input type="checkbox" class="h-4 w-4 rounded border-stone-400 text-brand-600" wire:model="{{ $row }}.isVeg"> Vegetarian
                        </label>
                    </div>

                    <div class="sm:col-span-3">
                        <label class="mb-1 block text-xs font-medium text-muted" for="option-mode-{{ $option->id }}">Price</label>
                        <select id="option-mode-{{ $option->id }}" class="field" wire:model.live="{{ $row }}.priceMode">
                            @foreach ($priceModes as $mode)
                                <option value="{{ $mode->value }}">{{ $mode->label() }}</option>
                            @endforeach
                        </select>
                        <x-form.error :name="$row.'.priceMode'" />
                        @if (($rows[$option->id]['priceMode'] ?? '') === 'flat')
                            <label class="sr-only" for="option-price-{{ $option->id }}">Price in taka</label>
                            <input id="option-price-{{ $option->id }}" type="text" inputmode="decimal" placeholder="Taka" class="field mt-2" wire:model="{{ $row }}.flatPrice">
                            <x-form.error :name="$row.'.flatPrice'" />
                        @else
                            <a href="{{ route('admin.option-groups.prices.edit', $optionGroupId) }}" class="mt-2 inline-block text-sm font-medium text-brand-700 underline">Set the prices per size</a>
                        @endif
                    </div>

                    <div class="flex flex-wrap items-center gap-2 sm:col-span-4 sm:justify-end">
                        @unless ($option->isAvailable)
                            <x-badge variant="soldout">Sold out</x-badge>
                        @endunless
                        <x-button size="sm" wire:click="saveRow({{ $option->id }})">Save</x-button>
                        <x-button size="sm" variant="outline" wire:click="toggle({{ $option->id }})">{{ $option->isAvailable ? 'Sold out' : 'Available' }}</x-button>
                        <x-button size="sm" variant="danger" wire:click="remove({{ $option->id }})"
                            wire:confirm="Move this option to the trash? It disappears from every product that offers it.">Delete</x-button>
                    </div>
                </div>

                <div class="mt-3 flex flex-wrap items-center gap-3 border-t border-line pt-3 text-sm">
                    <label for="option-image-{{ $option->id }}" class="text-xs font-medium text-muted">Image (at least 400 x 400, up to 2 MB)</label>
                    <input id="option-image-{{ $option->id }}" type="file" accept="image/jpeg,image/png,image/webp" wire:model="uploads.{{ $option->id }}" class="max-w-full text-sm">
                    <span wire:loading wire:target="uploads.{{ $option->id }}" class="text-muted">Uploading the preview...</span>
                    @if (isset($uploads[$option->id]))
                        @if ($uploads[$option->id]->isPreviewable())
                            <img src="{{ $uploads[$option->id]->temporaryUrl() }}" alt="Preview" class="h-14 w-14 rounded-lg object-cover">
                        @endif
                        <x-button size="sm" wire:click="saveImage({{ $option->id }})">Save image</x-button>
                    @endif
                    @if ($option->imagePath)
                        <x-button size="sm" variant="outline" wire:click="removeImage({{ $option->id }})" wire:confirm="Delete this image for good?">Remove image</x-button>
                    @endif
                    <x-form.error :name="'uploads.'.$option->id" />
                </div>
            </li>
        @endforeach
    </ul>

    @if ($options === [])
        <p class="text-sm text-muted">No options yet. Add the first one below.</p>
    @endif

    <form wire:submit="add" class="rounded-card border border-dashed border-stone-300 bg-white p-4">
        <h3 class="text-sm font-semibold">Add an option</h3>
        <div class="mt-3 grid gap-3 sm:grid-cols-12 sm:items-start">
            <div class="sm:col-span-4">
                <label class="mb-1 block text-xs font-medium text-muted" for="new-option-name">Name</label>
                <input id="new-option-name" type="text" maxlength="100" class="field" wire:model="new.name">
                <x-form.error name="new.name" />
                <label class="mt-2 inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" class="h-4 w-4 rounded border-stone-400 text-brand-600" wire:model="new.isVeg"> Vegetarian
                </label>
            </div>
            <div class="sm:col-span-4">
                <label class="mb-1 block text-xs font-medium text-muted" for="new-option-mode">Price</label>
                <select id="new-option-mode" class="field" wire:model.live="new.priceMode">
                    @foreach ($priceModes as $mode)
                        <option value="{{ $mode->value }}">{{ $mode->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-2">
                @if ($new['priceMode'] === 'flat')
                    <label class="mb-1 block text-xs font-medium text-muted" for="new-option-price">Taka</label>
                    <input id="new-option-price" type="text" inputmode="decimal" placeholder="60" class="field" wire:model="new.flatPrice">
                    <x-form.error name="new.flatPrice" />
                @endif
            </div>
            <div class="sm:col-span-2 sm:pt-5">
                <x-button type="submit" wire:loading.attr="disabled" wire:target="add">Add</x-button>
            </div>
        </div>
    </form>
</div>
