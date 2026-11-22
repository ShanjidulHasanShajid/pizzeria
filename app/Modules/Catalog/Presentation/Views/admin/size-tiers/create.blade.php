<x-layouts.admin title="Add size">
    <x-admin.page-header title="Add size" :breadcrumbs="[['label' => 'Admin', 'url' => route('admin.dashboard')], ['label' => 'Size tiers', 'url' => route('admin.size-tiers.index')], ['label' => 'Add']]" />

    <form method="POST" action="{{ route('admin.size-tiers.store') }}" novalidate>
        @csrf
        @include('catalog::admin.size-tiers._form', ['sizeTier' => null])
        <x-admin.save-bar :cancel="route('admin.size-tiers.index')" label="Create size" />
    </form>
</x-layouts.admin>
