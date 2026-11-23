{{-- $locations: list<LocationView>, in sort order. --}}
<x-layouts.admin title="Locations">
    <x-admin.page-header title="Locations" :breadcrumbs="[['label' => 'Admin', 'url' => route('admin.dashboard')], ['label' => 'Locations']]">
        <x-slot:actions>
            <x-link-button :href="route('admin.locations.create')">Add location</x-link-button>
        </x-slot:actions>
    </x-admin.page-header>

    @if ($locations === [])
        <x-empty-state title="No locations yet" message="Add your first branch." icon="map-pin" />
    @else
        <div class="max-w-4xl" x-data="{ state: '' }"
            x-on:sorted="state = 'saving'; axios.post(@js(route('admin.locations.reorder')), { ids: $event.detail.ids }).then(() => state = 'saved').catch(() => state = 'failed')">
            <x-admin.sortable-list label="Locations">
                @foreach ($locations as $location)
                    <li data-id="{{ $location->id }}" class="flex flex-wrap items-center gap-3 px-4 py-3">
                        <button type="button" data-handle aria-label="Drag {{ $location->name }} to reorder" class="cursor-grab text-muted hover:text-ink">
                            <x-icon name="bars-3" class="h-5 w-5" />
                        </button>
                        <span class="min-w-40 flex-1">
                            <span class="block font-medium">{{ $location->name }}</span>
                            <span class="block text-xs text-muted">{{ $location->address }}{{ $location->area === '' ? '' : ', '.$location->area }} &middot; {{ $location->phone }}</span>
                        </span>
                        @if ($location->isPickupAvailable)
                            <x-badge>Pickup</x-badge>
                        @endif
                        @if (! $location->isActive)
                            <x-badge variant="soldout">Switched off</x-badge>
                        @elseif ($location->isOpenNow)
                            <x-badge variant="veg">Open now</x-badge>
                        @else
                            <x-badge variant="lead">Closed now</x-badge>
                        @endif
                        <form method="POST" action="{{ route('admin.locations.toggle', $location->id) }}">
                            @csrf
                            @method('PATCH')
                            <x-button type="submit" size="sm" variant="outline">{{ $location->isActive ? 'Switch off' : 'Switch on' }}</x-button>
                        </form>
                        <x-link-button :href="route('admin.locations.edit', $location->id)" size="sm" variant="outline">Edit</x-link-button>
                        <x-button size="sm" variant="danger" x-on:click="$dispatch('open-modal', 'delete-location-{{ $location->id }}')">Delete</x-button>
                    </li>
                @endforeach
            </x-admin.sortable-list>

            <p class="mt-3 text-sm" role="status" aria-live="polite">
                <span x-show="state === 'saving'" x-cloak class="text-muted">Saving the new order...</span>
                <span x-show="state === 'saved'" x-cloak class="text-accent-700">New order saved.</span>
                <span x-show="state === 'failed'" x-cloak class="text-red-700">The new order could not be saved. Reload the page and try again.</span>
            </p>
        </div>

        @foreach ($locations as $location)
            <x-admin.confirm-delete :name="'delete-location-'.$location->id" :action="route('admin.locations.destroy', $location->id)" />
        @endforeach
    @endif
</x-layouts.admin>
