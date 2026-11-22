{{--
    $products: paginator of AdminProductRow. $filter: AdminProductFilter.
    $categories, $tags: id => name. $types: value => label. $imageUrl: path => URL.
--}}
<x-layouts.admin title="Products">
    <x-admin.page-header title="Products" :breadcrumbs="[['label' => 'Admin', 'url' => route('admin.dashboard')], ['label' => 'Products']]">
        <x-slot:actions>
            <x-link-button :href="route('admin.products.create')">Add product</x-link-button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.filter-bar :statuses="['all' => 'All', 'published' => 'Published', 'unpublished' => 'Hidden', 'sold_out' => 'Sold out', 'trashed' => 'Trash']"
        :current="$filter->status" placeholder="Search name or slug...">
        <div class="w-56">
            <x-form.select name="category" id="filter-category" label="Category" :options="$categories" placeholder="All categories" :value="$filter->categoryId" />
        </div>
        <div class="w-44">
            <x-form.select name="type" id="filter-type" label="Type" :options="$types" placeholder="All types" :value="$filter->type?->value" />
        </div>
        <div class="w-44">
            <x-form.select name="tag" id="filter-tag" label="Tag" :options="$tags" placeholder="All tags" :value="$filter->tagId" />
        </div>
    </x-admin.filter-bar>

    <div class="mt-4">
        <x-admin.table :columns="[['label' => 'Product'], ['label' => 'Category'], ['label' => 'Price'], ['label' => 'Status'], ['label' => 'Actions']]">
            @forelse ($products as $product)
                <tr>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <span class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-line bg-cream">
                                @if ($product->imagePath)
                                    <img src="{{ $imageUrl($product->imagePath) }}" alt="" class="h-full w-full object-cover">
                                @else
                                    <x-icon name="photo" class="h-5 w-5 text-stone-400" />
                                @endif
                            </span>
                            <span>
                                <span class="block font-medium">{{ $product->name }}</span>
                                <span class="block text-xs text-muted">{{ $product->slug }} · {{ $product->type->label() }}</span>
                            </span>
                        </div>
                    </td>
                    <td class="px-4 py-3">{{ $product->categoryName }}</td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <x-money :amount="$product->lowestPrice" :from="! $product->lowestPrice->equals($product->highestPrice)" />
                        <span class="block text-xs text-muted">{{ $product->variantCount }} {{ Str::plural('variant', $product->variantCount) }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex flex-wrap gap-1">
                            @if ($product->isDeleted)
                                <x-badge variant="soldout">In the trash</x-badge>
                            @else
                                <x-badge :variant="$product->isPublished ? 'veg' : 'neutral'">{{ $product->isPublished ? 'Published' : 'Hidden' }}</x-badge>
                                @unless ($product->isAvailable)
                                    <x-badge variant="soldout">Sold out</x-badge>
                                @endunless
                                @if ($product->isNew)
                                    <x-badge variant="new">New</x-badge>
                                @endif
                                @if ($product->isHot)
                                    <x-badge variant="hot">Hot</x-badge>
                                @endif
                            @endif
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex flex-wrap items-center gap-2">
                            @if ($product->isDeleted)
                                <form method="POST" action="{{ route('admin.products.restore', $product->id) }}">
                                    @csrf
                                    <x-button type="submit" size="sm" variant="outline">Restore</x-button>
                                </form>
                                @can('force-delete')
                                    <x-button size="sm" variant="danger" x-on:click="$dispatch('open-modal', 'force-delete-product-{{ $product->id }}')">Delete forever</x-button>
                                @endcan
                            @else
                                <form method="POST" action="{{ route('admin.products.toggle-published', $product->id) }}">
                                    @csrf
                                    @method('PATCH')
                                    <x-button type="submit" size="sm" variant="outline">{{ $product->isPublished ? 'Hide' : 'Publish' }}</x-button>
                                </form>
                                <form method="POST" action="{{ route('admin.products.toggle-availability', $product->id) }}">
                                    @csrf
                                    @method('PATCH')
                                    <x-button type="submit" size="sm" variant="outline">{{ $product->isAvailable ? 'Sold out' : 'Available' }}</x-button>
                                </form>
                                <x-link-button :href="route('admin.products.edit', $product->id)" size="sm" variant="outline">Edit</x-link-button>
                                <form method="POST" action="{{ route('admin.products.duplicate', $product->id) }}">
                                    @csrf
                                    <x-button type="submit" size="sm" variant="outline">Duplicate</x-button>
                                </form>
                                <x-button size="sm" variant="danger" x-on:click="$dispatch('open-modal', 'delete-product-{{ $product->id }}')">Delete</x-button>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-muted">No products match your filters.</td></tr>
            @endforelse
        </x-admin.table>
    </div>

    <div class="mt-4">{{ $products->links() }}</div>

    @foreach ($products as $product)
        @if ($product->isDeleted)
            @can('force-delete')
                <x-modal :name="'force-delete-product-'.$product->id" :title="'Delete '.$product->name.' forever?'">
                    <p class="text-sm text-muted">The product, its variants and its images are deleted for good. Old orders keep their own copy of the name and price. This cannot be undone.</p>
                    <form method="POST" action="{{ route('admin.products.force-destroy', $product->id) }}" class="mt-6 flex justify-end gap-3">
                        @csrf
                        @method('DELETE')
                        <x-button variant="outline" x-on:click="$dispatch('close-modal')">Cancel</x-button>
                        <x-button type="submit" variant="danger">Delete forever</x-button>
                    </form>
                </x-modal>
            @endcan
        @else
            <x-admin.confirm-delete :name="'delete-product-'.$product->id" :action="route('admin.products.destroy', $product->id)"
                :title="'Move '.$product->name.' to the trash?'" message="It disappears from the shop at once. You can restore it from the Trash tab." />
        @endif
    @endforeach
</x-layouts.admin>
