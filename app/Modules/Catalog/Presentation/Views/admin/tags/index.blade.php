{{-- $tags: list<TagRow>, in sort order --}}
<x-layouts.admin title="Tags">
    <x-admin.page-header title="Tags" :breadcrumbs="[['label' => 'Admin', 'url' => route('admin.dashboard')], ['label' => 'Tags']]">
        <x-slot:actions>
            <x-link-button :href="route('admin.tags.create')">Add tag</x-link-button>
        </x-slot:actions>
    </x-admin.page-header>

    <p class="mb-4 max-w-2xl text-sm text-muted">Badges on products: dietary (Veg, Halal), allergens (Contains Nuts) and flavour (Spicy). This order is the order of the badges on product cards.</p>

    @if ($tags === [])
        <x-empty-state title="No tags yet" message="Add the first tag, for example Veg.">
            <x-slot:action>
                <x-link-button :href="route('admin.tags.create')">Add tag</x-link-button>
            </x-slot:action>
        </x-empty-state>
    @else
        <div class="max-w-3xl" x-data="{ state: '' }"
            x-on:sorted="state = 'saving'; axios.post(@js(route('admin.tags.reorder')), { ids: $event.detail.ids }).then(() => state = 'saved').catch(() => state = 'failed')">
            <x-admin.sortable-list label="Tags">
                @foreach ($tags as $tag)
                    <li data-id="{{ $tag->id }}" class="flex flex-wrap items-center gap-3 px-4 py-3">
                        <button type="button" data-handle aria-label="Drag {{ $tag->name }} to reorder" class="cursor-grab text-muted hover:text-ink">
                            <x-icon name="bars-3" class="h-5 w-5" />
                        </button>
                        <span class="flex min-w-32 flex-1 items-center gap-2">
                            @if ($tag->icon)
                                <x-icon :name="$tag->icon" class="h-5 w-5 text-muted" />
                            @endif
                            <span class="font-medium">{{ $tag->name }}</span>
                            <span class="text-xs text-muted">{{ $tag->type->label() }}</span>
                        </span>
                        @unless ($tag->isActive)
                            <x-badge variant="soldout">Inactive</x-badge>
                        @endunless
                        <span class="text-sm text-muted">{{ $tag->productCount }} {{ Str::plural('product', $tag->productCount) }}</span>
                        <x-link-button :href="route('admin.tags.edit', $tag->id)" size="sm" variant="outline">Edit</x-link-button>
                        <x-button size="sm" variant="danger" x-on:click="$dispatch('open-modal', 'delete-tag-{{ $tag->id }}')">Delete</x-button>
                    </li>
                @endforeach
            </x-admin.sortable-list>

            <p class="mt-3 text-sm" role="status" aria-live="polite">
                <span x-show="state === 'saving'" x-cloak class="text-muted">Saving the new order...</span>
                <span x-show="state === 'saved'" x-cloak class="text-accent-700">New order saved.</span>
                <span x-show="state === 'failed'" x-cloak class="text-red-700">The new order could not be saved. Reload the page and try again.</span>
            </p>
        </div>

        @foreach ($tags as $tag)
            <x-admin.confirm-delete :name="'delete-tag-'.$tag->id" :action="route('admin.tags.destroy', $tag->id)"
                :title="'Move '.$tag->name.' to the trash?'"
                message="Products keep the tag, but the shop stops showing it. You can restore it from the Trash." />
        @endforeach
    @endif
</x-layouts.admin>
