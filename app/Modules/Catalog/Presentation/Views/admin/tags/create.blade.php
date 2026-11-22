<x-layouts.admin title="Add tag">
    <x-admin.page-header title="Add tag" :breadcrumbs="[['label' => 'Admin', 'url' => route('admin.dashboard')], ['label' => 'Tags', 'url' => route('admin.tags.index')], ['label' => 'Add']]" />

    <form method="POST" action="{{ route('admin.tags.store') }}" novalidate>
        @csrf
        @include('catalog::admin.tags._form', ['tag' => null])
        <x-admin.save-bar :cancel="route('admin.tags.index')" label="Create tag" />
    </form>
</x-layouts.admin>
