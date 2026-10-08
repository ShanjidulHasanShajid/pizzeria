<x-layouts.admin :title="'Edit '.$user->name">
    <x-admin.page-header :title="'Edit '.$user->name" :breadcrumbs="[['label' => 'Admin', 'url' => route('admin.dashboard')], ['label' => 'Admin users', 'url' => route('admin.admin-users.index')], ['label' => 'Edit']]" />

    <form method="POST" action="{{ route('admin.admin-users.update', $user->id) }}" novalidate>
        @csrf
        @method('PUT')
        @include('identity::admin.users._form', ['user' => $user, 'createRoles' => null])
        <x-admin.save-bar :cancel="route('admin.admin-users.index')" label="Save changes" />
    </form>

    <x-admin.form-section title="Role" description="Staff see orders only. Admins manage everything except admin users. Super admins manage everything.">
        @if ($isMe)
            <p class="text-sm text-muted sm:col-span-2">You cannot change your own role.</p>
        @else
            <form method="POST" action="{{ route('admin.admin-users.role', $user->id) }}" class="flex flex-wrap items-end gap-3 sm:col-span-2">
                @csrf
                @method('PATCH')
                <div class="w-56">
                    <x-form.select name="role" id="role-change" label="Role" :options="$allRoles" :value="$user->role->value" />
                </div>
                <x-button type="submit" variant="outline">Change role</x-button>
            </form>
        @endif
    </x-admin.form-section>
</x-layouts.admin>
