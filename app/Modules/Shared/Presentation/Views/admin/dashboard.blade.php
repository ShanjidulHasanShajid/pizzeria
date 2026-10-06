<x-layouts.admin title="Dashboard">
    <x-admin.page-header title="Dashboard" :breadcrumbs="[['label' => 'Admin']]" />

    <x-alert type="info" class="mb-6">This dashboard shows fake numbers. Real figures arrive in Phase 13.</x-alert>

    <dl class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([['Orders today', '24', null], ['Revenue today', null, 1865000], ['Pending orders', '5', null], ['New customers', '9', null]] as [$label, $count, $money])
            <x-card>
                <dt class="text-sm text-muted">{{ $label }}</dt>
                <dd class="mt-1 text-3xl font-bold">
                    @if ($money !== null)
                        <x-money :amount="$money" class="text-3xl" />
                    @else
                        {{ $count }}
                    @endif
                </dd>
            </x-card>
        @endforeach
    </dl>

    <h2 class="mb-3 mt-8 text-lg font-semibold">Recent orders</h2>
    <x-admin.table :columns="[['label' => 'Order'], ['label' => 'Customer'], ['label' => 'Total'], ['label' => 'Status']]">
        @foreach ([['#1042', 'Rahim', 129000, 'pending'], ['#1041', 'Nusrat', 79000, 'preparing'], ['#1040', 'Tanvir', 189000, 'delivered']] as [$number, $customer, $total, $status])
            <tr>
                <td class="px-4 py-3 font-medium">{{ $number }}</td>
                <td class="px-4 py-3">{{ $customer }}</td>
                <td class="px-4 py-3"><x-money :amount="$total" /></td>
                <td class="px-4 py-3"><x-badge :variant="$status">{{ ucfirst($status) }}</x-badge></td>
            </tr>
        @endforeach
    </x-admin.table>
</x-layouts.admin>