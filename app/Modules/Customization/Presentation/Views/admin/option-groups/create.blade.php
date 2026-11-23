<x-layouts.admin title="Add option group">
    <x-admin.page-header title="Add option group" :breadcrumbs="[['label' => 'Admin', 'url' => route('admin.dashboard')], ['label' => 'Option groups', 'url' => route('admin.option-groups.index')], ['label' => 'Add']]" />

    <form method="POST" action="{{ route('admin.option-groups.store') }}" novalidate>
        @csrf
        @include('customization::admin.option-groups._form', ['group' => null])
        <x-admin.save-bar :cancel="route('admin.option-groups.index')" label="Create option group" />
    </form>
</x-layouts.admin>
