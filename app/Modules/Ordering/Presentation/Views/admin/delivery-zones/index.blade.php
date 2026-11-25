{{-- $zones: list<DeliveryZoneRow>, in sort order --}}
<x-layouts.admin title="Delivery zones">
    <x-admin.page-header title="Delivery zones" :breadcrumbs="[['label' => 'Admin', 'url' => route('admin.dashboard')], ['label' => 'Delivery zones']]">
        <x-slot:actions>
            <x-link-button :href="route('admin.delivery-zones.create')">Add zone</x-link-button>
        </x-slot:actions>
    </x-admin.page-header>

    <p class="mb-4 max-w-2xl text-sm text-muted">The areas you deliver to, each with its own fee and minimum order. Customers see them in this order. Drag a row by its handle to change it.</p>

    @if ($zones === [])
        <x-empty-state title="No delivery zones yet" message="Without a zone, customers can only pick up." icon="map-pin">
            <x-slot:action>
                <x-link-button :href="route('admin.delivery-zones.create')">Add zone</x-link-button>
            </x-slot:action>
        </x-empty-state>
    @else
        {{-- admin.js sends a "sorted" event after a drop; Alpine posts the new order to the server. --}}
        <div class="max-w-4xl" x-data="{ state: '' }"
            x-on:sorted="state = 'saving'; axios.post(@js(route('admin.delivery-zones.reorder')), { ids: $event.detail.ids }).then(() => state = 'saved').catch(() => state = 'failed')">
            <x-admin.sortable-list label="Delivery zones">
                @foreach ($zones as $zone)
                    <li data-id="{{ $zone->id }}" class="flex flex-wrap items-center gap-3 px-4 py-3">
                        <button type="button" data-handle aria-label="Drag {{ $zone->name }} to reorder" class="cursor-grab text-muted hover:text-ink">
                            <x-icon name="bars-3" class="h-5 w-5" />
                        </button>
                        <span class="min-w-40 flex-1">
                            <span class="block font-medium">{{ $zone->name }}</span>
                            <span class="block text-xs text-muted">{{ $zone->branch === null ? 'No branch' : 'From '.$zone->branch->name }}</span>
                        </span>
                        <span class="text-sm tabular-nums">
                            @if ($zone->fee->isZero())
                                Free delivery
                            @else
                                Fee @money($zone->fee)
                            @endif
                            <span class="block text-xs text-muted">
                                @if ($zone->minimumOrder->isZero())
                                    No minimum
                                @else
                                    Minimum @money($zone->minimumOrder)
                                @endif
                            </span>
                        </span>
                        @if (! $zone->isActive)
                            <x-badge variant="soldout">Switched off</x-badge>
                        @elseif ($zone->branch === null)
                            <x-badge variant="soldout">Branch deleted</x-badge>
                        @elseif (! $zone->branch->isActive)
                            <x-badge variant="soldout">Branch switched off</x-badge>
                        @endif
                        <form method="POST" action="{{ route('admin.delivery-zones.toggle', $zone->id) }}">
                            @csrf
                            @method('PATCH')
                            <x-button type="submit" size="sm" variant="outline">{{ $zone->isActive ? 'Switch off' : 'Switch on' }}</x-button>
                        </form>
                        <x-link-button :href="route('admin.delivery-zones.edit', $zone->id)" size="sm" variant="outline">Edit</x-link-button>
                        <x-button size="sm" variant="danger" x-on:click="$dispatch('open-modal', 'delete-zone-{{ $zone->id }}')">Delete</x-button>
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
        @foreach ($zones as $zone)
            <x-admin.confirm-delete :name="'delete-zone-'.$zone->id" :action="route('admin.delivery-zones.destroy', $zone->id)"
                :title="'Move '.$zone->name.' to the trash?'" />
        @endforeach
    @endif
</x-layouts.admin>
