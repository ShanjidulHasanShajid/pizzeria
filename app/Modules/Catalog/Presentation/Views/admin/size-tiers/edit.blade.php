<x-layouts.admin :title="'Edit '.$sizeTier->name">
    <x-admin.page-header :title="'Edit '.$sizeTier->name" :breadcrumbs="[['label' => 'Admin', 'url' => route('admin.dashboard')], ['label' => 'Size tiers', 'url' => route('admin.size-tiers.index')], ['label' => 'Edit']]" />

    <form method="POST" action="{{ route('admin.size-tiers.update', $sizeTier->id) }}" novalidate>
        @csrf
        @method('PUT')
        @include('catalog::admin.size-tiers._form', ['sizeTier' => $sizeTier])
        <x-admin.save-bar :cancel="route('admin.size-tiers.index')" label="Save changes" />
    </form>
</x-layouts.admin>
