{{-- $stations: list<KitchenStationRow>, in sort order --}}
<x-layouts.admin title="Kitchen stations">
    <x-admin.page-header title="Kitchen stations" :breadcrumbs="[['label' => 'Admin', 'url' => route('admin.dashboard')], ['label' => 'Kitchen stations']]">
        <x-slot:actions>
            <x-link-button :href="route('admin.kitchen-stations.create')">Add station</x-link-button>
        </x-slot:actions>
    </x-admin.page-header>

    <p class="mb-4 max-w-2xl text-sm text-muted">Each category is prepared at one station (Oven, Fryer ...). Kitchen tickets are grouped by station. Drag a row by its handle to change the order.</p>

    @if ($stations === [])
        <x-empty-state title="No kitchen stations yet" message="Add the first station, for example Oven.">
            <x-slot:action>
                <x-link-button :href="route('admin.kitchen-stations.create')">Add station</x-link-button>
            </x-slot:action>
        </x-empty-state>
    @else
        {{-- admin.js sends a "sorted" event after a drop; Alpine posts the new order to the server. --}}
        <div class="max-w-3xl" x-data="{ state: '' }"
            x-on:sorted="state = 'saving'; axios.post(@js(route('admin.kitchen-stations.reorder')), { ids: $event.detail.ids }).then(() => state = 'saved').catch(() => state = 'failed')">
            <x-admin.sortable-list label="Kitchen stations">
                @foreach ($stations as $station)
                    <li data-id="{{ $station->id }}" class="flex flex-wrap items-center gap-3 px-4 py-3">
                        <button type="button" data-handle aria-label="Drag {{ $station->name }} to reorder" class="cursor-grab text-muted hover:text-ink">
                            <x-icon name="bars-3" class="h-5 w-5" />
                        </button>
                        <span class="min-w-32 flex-1 font-medium">{{ $station->name }}</span>
                        @unless ($station->isActive)
                            <x-badge variant="soldout">Inactive</x-badge>
                        @endunless
                        <span class="text-sm text-muted">{{ $station->categoryCount }} {{ Str::plural('category', $station->categoryCount) }}</span>
                        <x-link-button :href="route('admin.kitchen-stations.edit', $station->id)" size="sm" variant="outline">Edit</x-link-button>
                        @if ($station->categoryCount === 0)
                            <x-button size="sm" variant="danger" x-on:click="$dispatch('open-modal', 'delete-station-{{ $station->id }}')">Delete</x-button>
                        @else
                            <x-button size="sm" variant="danger" disabled title="Used by categories">Delete</x-button>
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

        {{-- The confirm dialogs live outside the list, so dragging never moves them. --}}
        @foreach ($stations as $station)
            @if ($station->categoryCount === 0)
                <x-admin.confirm-delete :name="'delete-station-'.$station->id" :action="route('admin.kitchen-stations.destroy', $station->id)"
                    :title="'Move '.$station->name.' to the trash?'" />
            @endif
        @endforeach
    @endif
</x-layouts.admin>
