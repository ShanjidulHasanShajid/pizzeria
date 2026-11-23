<x-layouts.admin :title="'Edit '.Str::lower($type->singular())">
    <x-admin.page-header :title="'Edit '.Str::lower($type->singular())" :breadcrumbs="[['label' => 'Admin', 'url' => route('admin.dashboard')], ['label' => $type->label(), 'url' => route('admin.'.$type->value.'.index')], ['label' => 'Edit']]" />

    <form method="POST" action="{{ route('admin.'.$type->value.'.update', $item->id) }}" enctype="multipart/form-data" novalidate>
        @csrf
        @method('PUT')
        @include('content::admin.items._form', ['item' => $item])
        <x-admin.save-bar :cancel="route('admin.'.$type->value.'.index')" label="Save changes" />
    </form>
</x-layouts.admin>
