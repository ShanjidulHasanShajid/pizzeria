{{-- $groups: list<OptionGroupRow>, in sort order --}}
<x-layouts.admin title="Option groups">
    <x-admin.page-header title="Option groups" :breadcrumbs="[['label' => 'Admin', 'url' => route('admin.dashboard')], ['label' => 'Option groups']]">
        <x-slot:actions>
            <x-link-button :href="route('admin.option-groups.availability')" variant="outline">Sold out today</x-link-button>
            <x-link-button :href="route('admin.option-groups.create')">Add option group</x-link-button>
        </x-slot:actions>
    </x-admin.page-header>

    <p class="mb-4 max-w-2xl text-sm text-muted">Choices that can be attached to products: crusts, toppings, dips, flavours. One group can be used on many products. A switched-off group is not offered anywhere.</p>

    @if ($groups === [])
        <x-empty-state title="No option groups yet" message="Add the first group, for example Crust.">
            <x-slot:action>
                <x-link-button :href="route('admin.option-groups.create')">Add option group</x-link-button>
            </x-slot:action>
        </x-empty-state>
    @else
        <div class="max-w-4xl" x-data="{ state: '' }"
            x-on:sorted="state = 'saving'; axios.post(@js(route('admin.option-groups.reorder')), { ids: $event.detail.ids }).then(() => state = 'saved').catch(() => state = 'failed')">
            <x-admin.sortable-list label="Option groups">
                @foreach ($groups as $group)
                    <li data-id="{{ $group->id }}" class="flex flex-wrap items-center gap-3 px-4 py-3">
                        <button type="button" data-handle aria-label="Drag {{ $group->name }} to reorder" class="cursor-grab text-muted hover:text-ink">
                            <x-icon name="bars-3" class="h-5 w-5" />
                        </button>
                        <span class="min-w-32 flex-1">
                            <span class="block font-medium">{{ $group->name }}</span>
                            <span class="block text-xs text-muted">{{ $group->displayTitle }}</span>
                        </span>
                        @unless ($group->isActive)
                            <x-badge variant="soldout">Inactive</x-badge>
                        @endunless
                        <span class="text-sm text-muted">
                            {{ $group->optionCount }} {{ Str::plural('option', $group->optionCount) }}@if ($group->soldOutCount > 0), {{ $group->soldOutCount }} sold out @endif
                        </span>
                        <span class="text-sm text-muted">{{ $group->productCount }} {{ Str::plural('product', $group->productCount) }}</span>
                        <x-link-button :href="route('admin.option-groups.prices.edit', $group->id)" size="sm" variant="outline">Prices</x-link-button>
                        <x-link-button :href="route('admin.option-groups.edit', $group->id)" size="sm" variant="outline">Edit</x-link-button>
                        @if ($group->productCount === 0)
                            <x-button size="sm" variant="danger" x-on:click="$dispatch('open-modal', 'delete-option-group-{{ $group->id }}')">Delete</x-button>
                        @else
                            <x-button size="sm" variant="danger" disabled title="Attached to products">Delete</x-button>
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

        @foreach ($groups as $group)
            @if ($group->productCount === 0)
                <x-admin.confirm-delete :name="'delete-option-group-'.$group->id" :action="route('admin.option-groups.destroy', $group->id)"
                    :title="'Move '.$group->name.' to the trash?'" message="Its options go with it." />
            @endif
        @endforeach
    @endif
</x-layouts.admin>
