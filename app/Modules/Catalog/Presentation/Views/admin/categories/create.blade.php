<x-layouts.admin title="Add category">
    <x-admin.page-header title="Add category" :breadcrumbs="[['label' => 'Admin', 'url' => route('admin.dashboard')], ['label' => 'Categories', 'url' => route('admin.categories.index')], ['label' => 'Add']]" />

    {{-- enctype="multipart/form-data" is needed, or the browser does not send the image file. --}}
    <form method="POST" action="{{ route('admin.categories.store') }}" enctype="multipart/form-data" novalidate>
        @csrf
        @include('catalog::admin.categories._form', ['category' => null])
        <x-admin.save-bar :cancel="route('admin.categories.index')" label="Create category" />
    </form>
</x-layouts.admin>
