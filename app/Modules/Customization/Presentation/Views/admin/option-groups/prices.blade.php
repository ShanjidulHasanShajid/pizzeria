{{-- $group: AdminOptionGroupDetail. $sizes: size tier id => name. --}}
@php
    $perSize = array_values(array_filter($group->options, fn ($option) => $option->isPricedPerSize));
    $flat = array_values(array_filter($group->options, fn ($option) => ! $option->isPricedPerSize));
@endphp
<x-layouts.admin :title="'Prices of '.$group->name">
    <x-admin.page-header :title="'Prices of '.$group->name" :breadcrumbs="[['label' => 'Admin', 'url' => route('admin.dashboard')], ['label' => 'Option groups', 'url' => route('admin.option-groups.index')], ['label' => $group->name, 'url' => route('admin.option-groups.edit', $group->id)], ['label' => 'Prices']]">
        <x-slot:actions>
            <x-link-button :href="route('admin.option-groups.edit', $group->id)" variant="outline">Edit options</x-link-button>
        </x-slot:actions>
    </x-admin.page-header>

    <p class="mb-4 max-w-2xl text-sm text-muted">Prices in taka. <strong>Empty</strong> means not offered at that size, <strong>0</strong> means free.</p>

    @if ($sizes === [] || $perSize === [])
        <x-empty-state title="Nothing to price per size"
            :message="$sizes === [] ? 'Add a size tier first.' : 'Every option of this group has one price for all sizes. Switch an option to a price per size on the edit page first.'" />
    @else
        @error('prices')
            <x-alert type="error" class="mb-4">{{ $message }}</x-alert>
        @enderror

        <form method="POST" action="{{ route('admin.option-groups.prices.update', $group->id) }}" novalidate>
            @csrf
            @method('PUT')
            <x-admin.table :columns="[['label' => 'Option'], ...array_map(fn ($name) => ['label' => $name], array_values($sizes))]">
                @foreach ($perSize as $option)
                    <tr>
                        <th scope="row" class="px-4 py-3 text-left font-medium">
                            {{ $option->name }}
                            @unless ($option->isAvailable)
                                <x-badge variant="soldout" class="ml-2">Sold out</x-badge>
                            @endunless
                            @error("prices.{$option->id}")
                                <span class="mt-1 block text-xs font-medium text-red-700" role="alert">{{ $message }}</span>
                            @enderror
                        </th>
                        @foreach ($sizes as $sizeId => $sizeName)
                            @php
                                $key = "prices.{$option->id}.{$sizeId}";
                                $id = "price-{$option->id}-{$sizeId}";
                            @endphp
                            <td class="px-4 py-3 align-top">
                                <label for="{{ $id }}" class="sr-only">{{ $option->name }}, {{ $sizeName }}, price in taka</label>
                                <input id="{{ $id }}" type="text" inputmode="decimal" name="prices[{{ $option->id }}][{{ $sizeId }}]"
                                    value="{{ old($key, $option->sizePricesTaka[$sizeId] ?? '') }}" placeholder="Not offered"
                                    @error($key) aria-invalid="true" aria-describedby="{{ $id }}-error" @enderror
                                    class="field w-28 {{ $errors->has($key) ? 'field-invalid' : '' }}">
                                @error($key)
                                    <p id="{{ $id }}-error" class="mt-1 w-28 text-xs font-medium text-red-700" role="alert">{{ $message }}</p>
                                @enderror
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </x-admin.table>

            <x-admin.save-bar :cancel="route('admin.option-groups.index')" label="Save prices" />
        </form>
    @endif

    @if ($flat !== [])
        <section class="mt-8 max-w-2xl">
            <h2 class="text-base font-semibold">Same price for every size</h2>
            <ul class="mt-2 divide-y divide-line rounded-card border border-line bg-white text-sm">
                @foreach ($flat as $option)
                    <li class="flex justify-between px-4 py-2"><span>{{ $option->name }}</span><span>৳{{ $option->flatPriceTaka }}</span></li>
                @endforeach
            </ul>
        </section>
    @endif
</x-layouts.admin>
