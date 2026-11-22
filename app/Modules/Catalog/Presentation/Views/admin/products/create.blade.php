<x-layouts.admin title="Add product">
    <x-admin.page-header title="Add product" :breadcrumbs="[['label' => 'Admin', 'url' => route('admin.dashboard')], ['label' => 'Products', 'url' => route('admin.products.index')], ['label' => 'Add']]" />

    <form method="POST" action="{{ route('admin.products.store') }}" novalidate>
        @csrf
        @include('catalog::admin.products._form', ['product' => null])
        <x-admin.save-bar :cancel="route('admin.products.index')" label="Create product" />
    </form>

    <p class="mt-6 text-sm text-muted">Images are added on the next page, after the product is created.</p>
</x-layouts.admin>
