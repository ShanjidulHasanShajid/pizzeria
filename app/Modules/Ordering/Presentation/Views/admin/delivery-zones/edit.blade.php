<x-layouts.admin :title="'Edit '.$zone->name">
    <x-admin.page-header :title="'Edit '.$zone->name" :breadcrumbs="[['label' => 'Admin', 'url' => route('admin.dashboard')], ['label' => 'Delivery zones', 'url' => route('admin.delivery-zones.index')], ['label' => 'Edit']]" />

    <form method="POST" action="{{ route('admin.delivery-zones.update', $zone->id) }}" novalidate>
        @csrf
        @method('PUT')
        @include('ordering::admin.delivery-zones._form')
        <x-admin.save-bar :cancel="route('admin.delivery-zones.index')" label="Save changes" />
    </form>
</x-layouts.admin>
