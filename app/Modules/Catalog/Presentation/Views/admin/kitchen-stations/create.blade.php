<x-layouts.admin title="Add kitchen station">
    <x-admin.page-header title="Add kitchen station" :breadcrumbs="[['label' => 'Admin', 'url' => route('admin.dashboard')], ['label' => 'Kitchen stations', 'url' => route('admin.kitchen-stations.index')], ['label' => 'Add']]" />

    <form method="POST" action="{{ route('admin.kitchen-stations.store') }}" novalidate>
        @csrf
        @include('catalog::admin.kitchen-stations._form', ['station' => null])
        <x-admin.save-bar :cancel="route('admin.kitchen-stations.index')" label="Create station" />
    </form>
</x-layouts.admin>
