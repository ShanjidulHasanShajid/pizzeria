{{--
    The view of the ProductImages Livewire component. A Livewire view needs ONE root element.
    $images: list<ProductImageView> in display order. $imageUrl: path => URL. $uploads: chosen files.
--}}
<div class="space-y-6">
    @if ($status !== '')
        <p class="text-sm text-accent-700" role="status">{{ $status }}</p>
    @endif

    {{-- The list is always there (also when empty), so admin.js can make it draggable on page load. --}}
    <ul data-sortable aria-label="Product images" class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4"
        x-data x-on:sorted="$wire.reorder($event.detail.ids)">
        @foreach ($images as $image)
            <li data-id="{{ $image->originalPath }}" wire:key="{{ $image->originalPath }}"
                class="overflow-hidden rounded-card border bg-white {{ $image->isMain ? 'border-brand-600 ring-2 ring-brand-600' : 'border-line' }}">
                <div class="relative aspect-square bg-cream">
                    <img src="{{ $imageUrl($image->thumbnailPath) }}" alt="Product image {{ $loop->iteration }}" class="h-full w-full object-cover">
                    <button type="button" data-handle aria-label="Drag image {{ $loop->iteration }} to reorder"
                        class="absolute left-2 top-2 cursor-grab rounded bg-white/90 p-1 text-muted hover:text-ink">
                        <x-icon name="bars-3" class="h-5 w-5" />
                    </button>
                    @if ($image->isMain)
                        <x-badge variant="hot" class="absolute right-2 top-2">Main</x-badge>
                    @endif
                </div>
                <div class="flex flex-wrap gap-2 p-2">
                    @unless ($image->isMain)
                        <x-button size="sm" variant="outline" wire:click="setMain(@js($image->originalPath))">Make main</x-button>
                    @endunless
                    <x-button size="sm" variant="danger" wire:click="remove(@js($image->originalPath))"
                        wire:confirm="Delete this image for good? This cannot be undone.">Delete</x-button>
                </div>
            </li>
        @endforeach
    </ul>

    @if ($images === [])
        <p class="text-sm text-muted">No images yet. The first image you add becomes the main image.</p>
    @endif

    <div class="rounded-card border border-dashed border-stone-300 bg-white p-4">
        <label for="product-image-uploads" class="mb-1 block text-sm font-medium text-ink">Add images</label>
        <input id="product-image-uploads" type="file" wire:model="uploads" multiple accept="image/jpeg,image/png,image/webp"
            class="block w-full text-sm file:mr-3 file:rounded-btn file:border-0 file:bg-brand-600 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-brand-700">
        <p class="mt-1 text-xs text-muted">JPG, PNG or WebP, at least 400 x 400 pixels, at most 2 MB each, up to 10 images per product. Square photos look best.</p>

        <p wire:loading wire:target="uploads" class="mt-2 text-sm text-muted">Uploading the preview...</p>

        @if ($uploads !== [])
            <div class="mt-4 flex flex-wrap gap-3">
                @foreach ($uploads as $upload)
                    {{-- isPreviewable(): a temporary preview URL exists only for image files. --}}
                    @if ($upload->isPreviewable())
                        <img src="{{ $upload->temporaryUrl() }}" alt="Preview {{ $loop->iteration }}" class="h-20 w-20 rounded-lg object-cover">
                    @endif
                @endforeach
            </div>
            <x-button class="mt-4" wire:click="save" wire:loading.attr="disabled" wire:target="save">Save {{ count($uploads) }} {{ Str::plural('image', count($uploads)) }}</x-button>
        @endif

        @error('uploads')
            <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
        @enderror
        @foreach ($errors->get('uploads.*') as $messages)
            <p class="mt-2 text-sm text-red-700">{{ $messages[0] }}</p>
        @endforeach
    </div>
</div>
