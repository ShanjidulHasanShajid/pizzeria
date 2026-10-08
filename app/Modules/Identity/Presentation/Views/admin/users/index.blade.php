@php
    $roleBadge = ['super_admin' => 'hot', 'admin' => 'new', 'staff' => 'confirmed', 'customer' => 'neutral'];
    $me = (int) auth()->id();
@endphp
<x-layouts.admin title="Admin users">
    <x-admin.page-header title="Admin users" :breadcrumbs="[['label' => 'Admin', 'url' => route('admin.dashboard')], ['label' => 'Admin users']]">
        <x-slot:actions>
            <x-link-button :href="route('admin.admin-users.create')">Add user</x-link-button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.filter-bar :statuses="['all' => 'All', 'active' => 'Active', 'blocked' => 'Blocked', 'deleted' => 'Deleted']" :current="$status" placeholder="Search name, email or phone...">
        <div class="w-48">
            <x-form.select name="role" id="filter-role" label="Role" :options="$roleOptions" placeholder="All roles" :value="$role?->value" />
        </div>
    </x-admin.filter-bar>

    <div class="mt-4">
        <x-admin.table :columns="[['label' => 'Name'], ['label' => 'Email'], ['label' => 'Phone'], ['label' => 'Role'], ['label' => 'Status'], ['label' => 'Actions']]">
            @forelse ($users as $user)
                <tr>
                    <td class="px-4 py-3 font-medium">
                        {{ $user->name }}
                        @if ($user->id === $me)
                            <span class="text-xs font-normal text-muted">(you)</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">{{ $user->email }}</td>
                    <td class="px-4 py-3">{{ $user->phone ?? '-' }}</td>
                    <td class="px-4 py-3"><x-badge :variant="$roleBadge[$user->role->value]">{{ $user->role->label() }}</x-badge></td>
                    <td class="px-4 py-3">
                        @if ($user->isDeleted)
                            <x-badge variant="soldout">Deleted</x-badge>
                        @elseif ($user->isBlocked)
                            <x-badge variant="cancelled">Blocked</x-badge>
                        @else
                            <x-badge variant="veg">Active</x-badge>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex flex-wrap items-center gap-2">
                            @if ($user->isDeleted)
                                <form method="POST" action="{{ route('admin.admin-users.restore', $user->id) }}">
                                    @csrf
                                    <x-button type="submit" size="sm" variant="outline">Restore</x-button>
                                </form>
                            @else
                                <x-link-button :href="route('admin.admin-users.edit', $user->id)" size="sm" variant="outline">Edit</x-link-button>

                                @if ($user->id !== $me)
                                    <form method="POST" action="{{ route($user->isBlocked ? 'admin.admin-users.unblock' : 'admin.admin-users.block', $user->id) }}">
                                        @csrf
                                        <x-button type="submit" size="sm" variant="outline">{{ $user->isBlocked ? 'Unblock' : 'Block' }}</x-button>
                                    </form>
                                    <x-button size="sm" variant="danger" x-on:click="$dispatch('open-modal', 'delete-user-{{ $user->id }}')">Delete</x-button>
                                    <x-admin.confirm-delete :name="'delete-user-'.$user->id" :action="route('admin.admin-users.destroy', $user->id)"
                                        :title="'Move '.$user->name.' to the trash?'" message="They will no longer be able to sign in. You can restore them from the Deleted tab." />
                                @endif
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-muted">No users match your filters.</td></tr>
            @endforelse
        </x-admin.table>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
</x-layouts.admin>
