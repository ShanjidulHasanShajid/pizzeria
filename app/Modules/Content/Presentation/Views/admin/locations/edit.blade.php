<x-layouts.admin title="Edit location">
    <x-admin.page-header title="Edit location" :breadcrumbs="[['label' => 'Admin', 'url' => route('admin.dashboard')], ['label' => 'Locations', 'url' => route('admin.locations.index')], ['label' => 'Edit']]" />

    <form method="POST" action="{{ route('admin.locations.update', $location->id) }}" novalidate>
        @csrf
        @method('PUT')
        @include('content::admin.locations._form', ['location' => $location])
        <x-admin.save-bar :cancel="route('admin.locations.index')" label="Save changes" />
    </form>
</x-layouts.admin>
