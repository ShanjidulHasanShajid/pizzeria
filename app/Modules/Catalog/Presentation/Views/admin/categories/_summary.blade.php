{{--
    Thumbnail, name and badges of one category. $row: AdminCategoryRow. $imageUrl: path => URL.
--}}
<span class="flex min-w-48 flex-1 items-center gap-3">
    <span class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-line bg-cream">
        @if ($row->imagePath)
            <img src="{{ $imageUrl($row->imagePath) }}" alt="" class="h-full w-full object-cover">
        @else
            <x-icon name="photo" class="h-5 w-5 text-stone-400" />
        @endif
    </span>
    <span>
        <span class="block font-medium">{{ $row->name }}</span>
        <span class="block text-xs text-muted">{{ $row->slug }} · {{ $row->kitchenStationName }} · {{ $row->productCount }} {{ Str::plural('product', $row->productCount) }}</span>
    </span>
    @unless ($row->isActive)
        <x-badge variant="soldout">Inactive</x-badge>
    @endunless
    @if ($row->isFeatured)
        <x-badge variant="hot">Featured</x-badge>
    @endif
    @unless ($row->showInMegaMenu)
        <x-badge>Not in menu</x-badge>
    @endunless
</span>
