<x-layouts.admin title="Add delivery zone">
    <x-admin.page-header title="Add delivery zone" :breadcrumbs="[['label' => 'Admin', 'url' => route('admin.dashboard')], ['label' => 'Delivery zones', 'url' => route('admin.delivery-zones.index')], ['label' => 'Add']]" />

    @if ($branches === [])
        <x-empty-state title="Add a location first" message="Every delivery zone is served by a branch." icon="map-pin">
            <x-slot:action>
                <x-link-button :href="route('admin.locations.create')">Add location</x-link-button>
            </x-slot:action>
        </x-empty-state>
    @else
        <form method="POST" action="{{ route('admin.delivery-zones.store') }}" novalidate>
            @csrf
            @include('ordering::admin.delivery-zones._form', ['zone' => null])
            <x-admin.save-bar :cancel="route('admin.delivery-zones.index')" label="Create" />
        </form>
    @endif
</x-layouts.admin>
