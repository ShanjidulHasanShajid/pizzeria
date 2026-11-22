<x-layouts.admin :title="'Edit '.$product->name">
    <x-admin.page-header :title="'Edit '.$product->name" :breadcrumbs="[['label' => 'Admin', 'url' => route('admin.dashboard')], ['label' => 'Products', 'url' => route('admin.products.index')], ['label' => 'Edit']]">
        <x-slot:actions>
            <form method="POST" action="{{ route('admin.products.duplicate', $product->id) }}">
                @csrf
                <x-button type="submit" variant="outline">Duplicate</x-button>
            </form>
        </x-slot:actions>
    </x-admin.page-header>

    {{-- Images first: they are saved at once by the Livewire component, without the Save button below. --}}
    <x-admin.form-section title="Images" description="Changes here are saved at once. Drag to reorder; the main image is shown on the product card.">
        <div class="sm:col-span-2">
            <livewire:catalog.admin.product-images :product-id="$product->id" />
        </div>
    </x-admin.form-section>

    <form method="POST" action="{{ route('admin.products.update', $product->id) }}" novalidate>
        @csrf
        @method('PUT')
        @include('catalog::admin.products._form', ['product' => $product])
        <x-admin.save-bar :cancel="route('admin.products.index')" label="Save changes" />
    </form>
</x-layouts.admin>
