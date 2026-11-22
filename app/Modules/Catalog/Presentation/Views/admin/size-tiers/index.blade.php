{{-- $sizeTiers: list<SizeTierRow>, in sort order --}}
<x-layouts.admin title="Size tiers">
    <x-admin.page-header title="Size tiers" :breadcrumbs="[['label' => 'Admin', 'url' => route('admin.dashboard')], ['label' => 'Size tiers']]">
        <x-slot:actions>
            <x-link-button :href="route('admin.size-tiers.create')">Add size</x-link-button>
        </x-slot:actions>
    </x-admin.page-header>

    <p class="mb-4 max-w-2xl text-sm text-muted">The sizes pizzas and pies come in. This order is the order of the size buttons on every product page. An inactive size cannot be chosen for new variants.</p>

    @if ($sizeTiers === [])
        <x-empty-state title="No sizes yet" message="Add the first size, for example Personal.">
            <x-slot:action>
                <x-link-button :href="route('admin.size-tiers.create')">Add size</x-link-button>
            </x-slot:action>
        </x-empty-state>
    @else
        <div class="max-w-3xl" x-data="{ state: '' }"
            x-on:sorted="state = 'saving'; axios.post(@js(route('admin.size-tiers.reorder')), { ids: $event.detail.ids }).then(() => state = 'saved').catch(() => state = 'failed')">
            <x-admin.sortable-list label="Size tiers">
                @foreach ($sizeTiers as $sizeTier)
                    <li data-id="{{ $sizeTier->id }}" class="flex flex-wrap items-center gap-3 px-4 py-3">
                        <button type="button" data-handle aria-label="Drag {{ $sizeTier->name }} to reorder" class="cursor-grab text-muted hover:text-ink">
                            <x-icon name="bars-3" class="h-5 w-5" />
                        </button>
                        <span class="min-w-32 flex-1">
                            <span class="font-medium">{{ $sizeTier->name }}</span>
                            <span class="ml-2 text-xs text-muted">{{ $sizeTier->slug }}</span>
                        </span>
                        @unless ($sizeTier->isActive)
                            <x-badge variant="soldout">Inactive</x-badge>
                        @endunless
                        <span class="text-sm text-muted">{{ $sizeTier->variantCount }} {{ Str::plural('variant', $sizeTier->variantCount) }}</span>
                        <form method="POST" action="{{ route('admin.size-tiers.toggle', $sizeTier->id) }}">
                            @csrf
                            @method('PATCH')
                            <x-button type="submit" size="sm" variant="outline">{{ $sizeTier->isActive ? 'Switch off' : 'Switch on' }}</x-button>
                        </form>
                        <x-link-button :href="route('admin.size-tiers.edit', $sizeTier->id)" size="sm" variant="outline">Edit</x-link-button>
                        @if ($sizeTier->variantCount === 0)
                            <x-button size="sm" variant="danger" x-on:click="$dispatch('open-modal', 'delete-size-{{ $sizeTier->id }}')">Delete</x-button>
                        @else
                            <x-button size="sm" variant="danger" disabled title="Used by product variants">Delete</x-button>
                        @endif
                    </li>
                @endforeach
            </x-admin.sortable-list>

            <p class="mt-3 text-sm" role="status" aria-live="polite">
                <span x-show="state === 'saving'" x-cloak class="text-muted">Saving the new order...</span>
                <span x-show="state === 'saved'" x-cloak class="text-accent-700">New order saved.</span>
                <span x-show="state === 'failed'" x-cloak class="text-red-700">The new order could not be saved. Reload the page and try again.</span>
            </p>
        </div>

        @foreach ($sizeTiers as $sizeTier)
            @if ($sizeTier->variantCount === 0)
                <x-admin.confirm-delete :name="'delete-size-'.$sizeTier->id" :action="route('admin.size-tiers.destroy', $sizeTier->id)"
                    :title="'Move '.$sizeTier->name.' to the trash?'" />
            @endif
        @endforeach
    @endif
</x-layouts.admin>
