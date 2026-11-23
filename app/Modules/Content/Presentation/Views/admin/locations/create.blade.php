<x-layouts.admin title="Add location">
    <x-admin.page-header title="Add location" :breadcrumbs="[['label' => 'Admin', 'url' => route('admin.dashboard')], ['label' => 'Locations', 'url' => route('admin.locations.index')], ['label' => 'Add']]" />

    <form method="POST" action="{{ route('admin.locations.store') }}" novalidate>
        @csrf
        @include('content::admin.locations._form', ['location' => null])
        <x-admin.save-bar :cancel="route('admin.locations.index')" label="Create" />
    </form>
</x-layouts.admin>
