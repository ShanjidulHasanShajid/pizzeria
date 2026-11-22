<x-layouts.admin :title="'Edit '.$station->name">
    <x-admin.page-header :title="'Edit '.$station->name" :breadcrumbs="[['label' => 'Admin', 'url' => route('admin.dashboard')], ['label' => 'Kitchen stations', 'url' => route('admin.kitchen-stations.index')], ['label' => 'Edit']]" />

    <form method="POST" action="{{ route('admin.kitchen-stations.update', $station->id) }}" novalidate>
        @csrf
        @method('PUT')
        @include('catalog::admin.kitchen-stations._form', ['station' => $station])
        <x-admin.save-bar :cancel="route('admin.kitchen-stations.index')" label="Save changes" />
    </form>
</x-layouts.admin>
