<x-layouts.admin title="Add user">
    <x-admin.page-header title="Add user" :breadcrumbs="[['label' => 'Admin', 'url' => route('admin.dashboard')], ['label' => 'Admin users', 'url' => route('admin.admin-users.index')], ['label' => 'Add']]" />

    <form method="POST" action="{{ route('admin.admin-users.store') }}" novalidate>
        @csrf
        @include('identity::admin.users._form', ['user' => null, 'createRoles' => $createRoles])
        <x-admin.save-bar :cancel="route('admin.admin-users.index')" label="Create user" />
    </form>
</x-layouts.admin>
