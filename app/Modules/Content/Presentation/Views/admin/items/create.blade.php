<x-layouts.admin :title="'Add '.Str::lower($type->singular())">
    <x-admin.page-header :title="'Add '.Str::lower($type->singular())" :breadcrumbs="[['label' => 'Admin', 'url' => route('admin.dashboard')], ['label' => $type->label(), 'url' => route('admin.'.$type->value.'.index')], ['label' => 'Add']]" />

    <form method="POST" action="{{ route('admin.'.$type->value.'.store') }}" enctype="multipart/form-data" novalidate>
        @csrf
        @include('content::admin.items._form', ['item' => null])
        <x-admin.save-bar :cancel="route('admin.'.$type->value.'.index')" label="Create" />
    </form>
</x-layouts.admin>
