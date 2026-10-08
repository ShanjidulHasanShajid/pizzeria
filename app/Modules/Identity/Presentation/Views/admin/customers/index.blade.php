<x-layouts.admin title="Customers">
    <x-admin.page-header title="Customers" :breadcrumbs="[['label' => 'Admin', 'url' => route('admin.dashboard')], ['label' => 'Customers']]" />

    <x-admin.filter-bar :statuses="['all' => 'All', 'active' => 'Active', 'blocked' => 'Blocked', 'deleted' => 'Deleted']" :current="$status" placeholder="Search name, email or phone..." />

    <div class="mt-4">
        <x-admin.table :columns="[['label' => 'Name'], ['label' => 'Phone'], ['label' => 'Email'], ['label' => 'Joined'], ['label' => 'Status'], ['label' => 'Actions']]">
            @forelse ($customers as $customer)
                <tr>
                    <td class="px-4 py-3 font-medium">{{ $customer->name }}</td>
                    <td class="px-4 py-3">{{ $customer->phone ?? '-' }}</td>
                    <td class="px-4 py-3">{{ $customer->email }}</td>
                    <td class="px-4 py-3">{{ $customer->createdAt->format('d M Y') }}</td>
                    <td class="px-4 py-3">
                        @if ($customer->isDeleted)
                            <x-badge variant="soldout">Deleted</x-badge>
                        @elseif ($customer->isBlocked)
                            <x-badge variant="cancelled">Blocked</x-badge>
                        @else
                            <x-badge variant="veg">Active</x-badge>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @unless ($customer->isDeleted)
                            <form method="POST" action="{{ route($customer->isBlocked ? 'admin.customers.unblock' : 'admin.customers.block', $customer->id) }}">
                                @csrf
                                <x-button type="submit" size="sm" variant="outline">{{ $customer->isBlocked ? 'Unblock' : 'Block' }}</x-button>
                            </form>
                        @endunless
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-muted">No customers match your filters.</td></tr>
            @endforelse
        </x-admin.table>
    </div>

    <div class="mt-4">{{ $customers->links() }}</div>
</x-layouts.admin>
