<x-layouts.admin :title="'Edit '.$category->name">
    <x-admin.page-header :title="'Edit '.$category->name" :breadcrumbs="[['label' => 'Admin', 'url' => route('admin.dashboard')], ['label' => 'Categories', 'url' => route('admin.categories.index')], ['label' => 'Edit']]" />

    <form method="POST" action="{{ route('admin.categories.update', $category->id) }}" enctype="multipart/form-data" novalidate>
        @csrf
        @method('PUT')
        @include('catalog::admin.categories._form', ['category' => $category])
        <x-admin.save-bar :cancel="route('admin.categories.index')" label="Save changes" />
    </form>
</x-layouts.admin>
