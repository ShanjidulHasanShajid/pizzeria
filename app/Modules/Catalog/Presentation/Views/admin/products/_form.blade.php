{{--
    Included by create and edit. $product: AdminProductDetail when editing, null when creating.
    $categories, $sizeTiers, $tags: id => name. $leadTimeClasses: value => label.
--}}
@php
    // The variant rows for Alpine: the old input after a failed save, else the saved variants, else one empty row.
    $oldRows = old('variants');

    if (is_array($oldRows)) {
        $rows = array_values(array_map(fn (array $row): array => [
            'id' => $row['id'] ?? null,
            'label' => (string) ($row['label'] ?? ''),
            'price' => (string) ($row['price'] ?? ''),
            'size_tier_id' => (string) ($row['size_tier_id'] ?? ''),
            'is_available' => ($row['is_available'] ?? '1') !== '0',
        ], $oldRows));
    } elseif ($product) {
        $rows = array_map(fn ($variant): array => [
            'id' => $variant->id,
            'label' => $variant->label,
            'price' => $variant->priceTaka,
            'size_tier_id' => (string) ($variant->sizeTierId ?? ''),
            'is_available' => $variant->isAvailable,
        ], $product->variants);
    } else {
        $rows = [['id' => null, 'label' => 'Regular', 'price' => '', 'size_tier_id' => '', 'is_available' => true]];
    }

    $savedDefault = 0;

    foreach ($product?->variants ?? [] as $index => $variant) {
        if ($variant->isDefault) {
            $savedDefault = $index;
        }
    }

    $defaultIndex = (int) old('default_variant', $savedDefault);

    // Errors of the rows ("variants.1.price" => message), so each row can show its own.
    $rowErrors = [];

    foreach ($errors->getMessages() as $field => $messages) {
        if (str_starts_with($field, 'variants.')) {
            $rowErrors[$field] = $messages[0];
        }
    }

    $chosenTags = array_map('intval', (array) old('tags', $product?->tagIds ?? []));
@endphp

<x-admin.form-section title="Basics" description="What the product is and where it appears in the menu.">
    <x-form.select name="category_id" label="Category" :options="$categories" :value="$product?->categoryId" placeholder="Choose a category" required />
    <div>
        <p class="mb-1 block text-sm font-medium text-ink">Type</p>
        <p class="text-sm text-muted">{{ $product?->type->label() ?? 'Standard item' }}. Combos and the build-your-own pizza get their own screens in Phases 15 and 16.</p>
    </div>
    <x-form.input name="name" label="Name" :value="$product?->name" required maxlength="150" />
    <x-form.input name="slug" label="Slug" :value="$product?->slug" maxlength="120"
        :help="$product ? 'Part of the web address. Leave empty to keep it.' : 'Part of the web address. Leave empty and it is made from the name.'" />
    <div class="sm:col-span-2">
        <x-form.textarea name="short_description" label="Short description" :value="$product?->shortDescription" rows="2" maxlength="255"
            help="One line for the product card, for example the toppings." />
    </div>
    <div class="sm:col-span-2">
        <x-form.textarea name="description" label="Description" :value="$product?->description" rows="5" maxlength="5000"
            help="Optional. Shown on the product page." />
    </div>
</x-admin.form-section>

<x-admin.form-section title="Variants" description="Sizes or portions, each with its own price in taka. One is the default (chosen when the page opens). The order here is the order on the product page.">
    <div class="space-y-3 sm:col-span-2"
        x-data="{
            rows: @js($rows).map((row, index) => ({ ...row, uid: index })),
            nextUid: @js(count($rows)),
            defaultIndex: @js($defaultIndex),
            errors: @js($rowErrors),
            add() {
                this.rows.push({ id: null, label: '', price: '', size_tier_id: '', is_available: true, uid: this.nextUid++ })
            },
            remove(index) {
                if (this.rows.length === 1) return
                this.rows.splice(index, 1)
                if (this.defaultIndex === index) this.defaultIndex = 0
                else if (this.defaultIndex > index) this.defaultIndex--
            },
            move(index, step) {
                const other = index + step
                if (other < 0 || other >= this.rows.length) return
                const moved = this.rows[index]
                this.rows[index] = this.rows[other]
                this.rows[other] = moved
                if (this.defaultIndex === index) this.defaultIndex = other
                else if (this.defaultIndex === other) this.defaultIndex = index
            },
            error(index, field) { return this.errors['variants.' + index + '.' + field] ?? '' },
        }">
        @error('variants')
            <x-alert type="error">{{ $message }}</x-alert>
        @enderror
        @error('default_variant')
            <x-alert type="error">{{ $message }}</x-alert>
        @enderror

        <template x-for="(row, index) in rows" :key="row.uid">
            <div class="grid gap-3 rounded-card border border-line bg-white p-3 sm:grid-cols-12 sm:items-start">
                <input type="hidden" :name="'variants[' + index + '][id]'" :value="row.id ?? ''">

                <div class="sm:col-span-3">
                    <label class="mb-1 block text-xs font-medium text-muted" :for="'variant-label-' + index">Label</label>
                    <input type="text" class="field" maxlength="60" placeholder="Large or 6 pcs"
                        :id="'variant-label-' + index" :name="'variants[' + index + '][label]'" x-model="row.label">
                    <p class="mt-1 text-xs text-red-700" x-show="error(index, 'label')" x-text="error(index, 'label')"></p>
                </div>

                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-medium text-muted" :for="'variant-price-' + index">Price (৳)</label>
                    <input type="text" inputmode="decimal" class="field" placeholder="450"
                        :id="'variant-price-' + index" :name="'variants[' + index + '][price]'" x-model="row.price">
                    <p class="mt-1 text-xs text-red-700" x-show="error(index, 'price')" x-text="error(index, 'price')"></p>
                </div>

                <div class="sm:col-span-3">
                    <label class="mb-1 block text-xs font-medium text-muted" :for="'variant-size-' + index">Size</label>
                    <select class="field" :id="'variant-size-' + index" :name="'variants[' + index + '][size_tier_id]'" x-model="row.size_tier_id">
                        <option value="">No size</option>
                        @foreach ($sizeTiers as $sizeTierId => $sizeTierName)
                            <option value="{{ $sizeTierId }}">{{ $sizeTierName }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-red-700" x-show="error(index, 'size_tier_id')" x-text="error(index, 'size_tier_id')"></p>
                </div>

                <div class="flex flex-wrap items-center gap-4 sm:col-span-2 sm:pt-6">
                    <label class="inline-flex items-center gap-2 text-sm">
                        <input type="radio" name="default_variant" class="h-4 w-4 text-brand-600" :value="index" x-model.number="defaultIndex">
                        Default
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm">
                        <input type="hidden" :name="'variants[' + index + '][is_available]'" :value="row.is_available ? 1 : 0">
                        <input type="checkbox" class="h-4 w-4 rounded text-brand-600" x-model="row.is_available">
                        Available
                    </label>
                </div>

                <div class="flex gap-1 sm:col-span-2 sm:justify-end sm:pt-6">
                    <button type="button" class="rounded p-1 text-muted hover:text-ink disabled:opacity-30" x-on:click="move(index, -1)" :disabled="index === 0"
                        :aria-label="'Move ' + (row.label || 'variant') + ' up'"><x-icon name="chevron-left" class="h-5 w-5 rotate-90" /></button>
                    <button type="button" class="rounded p-1 text-muted hover:text-ink disabled:opacity-30" x-on:click="move(index, 1)" :disabled="index === rows.length - 1"
                        :aria-label="'Move ' + (row.label || 'variant') + ' down'"><x-icon name="chevron-right" class="h-5 w-5 rotate-90" /></button>
                    <button type="button" class="rounded p-1 text-red-700 hover:text-red-900 disabled:opacity-30" x-on:click="remove(index)" :disabled="rows.length === 1"
                        :aria-label="'Remove ' + (row.label || 'variant')"><x-icon name="trash" class="h-5 w-5" /></button>
                </div>
            </div>
        </template>

        <x-button variant="outline" x-on:click="add()">Add variant</x-button>
    </div>
</x-admin.form-section>

<x-admin.form-section title="Tags" description="Dietary, allergen and flavour badges. Inactive tags are not shown in the shop.">
    <fieldset class="sm:col-span-2">
        <legend class="sr-only">Tags</legend>
        <div class="grid gap-2 sm:grid-cols-3">
            @foreach ($tags as $tagId => $tagName)
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" name="tags[]" value="{{ $tagId }}" class="h-4 w-4 rounded text-brand-600" @checked(in_array($tagId, $chosenTags, true))>
                    {{ $tagName }}
                </label>
            @endforeach
        </div>
        <x-form.error name="tags" />
    </fieldset>
</x-admin.form-section>

<x-admin.form-section title="Timing and badges" description="How long the kitchen needs, and the small New and Hot labels.">
    <x-form.select name="lead_time_class" label="Lead time" :options="$leadTimeClasses" :value="$product?->leadTimeClass->value ?? 'made_to_order'" required />
    <x-form.input name="lead_time_minutes" type="number" label="Minutes" :value="$product?->leadTimeMinutes ?? 20" required min="0" max="10080"
        help="Made to order: cooking time (20). Same day: about 180. Next day: 1440." />
    <div class="space-y-4 sm:col-span-2">
        <x-form.toggle name="is_new" label="New" :checked="$product?->isNew ?? false" />
        <x-form.toggle name="is_hot" label="Hot (shown in the home page's hot list)" :checked="$product?->isHot ?? false" />
    </div>
</x-admin.form-section>

<x-admin.form-section title="Visibility" description="Hidden products return 404 in the shop. Sold-out products stay visible but cannot be ordered.">
    <div class="space-y-4 sm:col-span-2">
        <x-form.toggle name="is_published" label="Published" :checked="$product?->isPublished ?? false" />
        <x-form.toggle name="is_available" label="Available today (switch off for sold out)" :checked="$product?->isAvailable ?? true" />
    </div>
</x-admin.form-section>
