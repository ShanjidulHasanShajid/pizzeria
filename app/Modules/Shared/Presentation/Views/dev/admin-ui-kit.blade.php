@php
    $rows = [
        ['id' => 1, 'name' => 'Margherita', 'price' => 59000, 'status' => 'ready'],
        ['id' => 2, 'name' => 'Pepperoni Feast', 'price' => 79000, 'status' => 'ready'],
        ['id' => 3, 'name' => 'Chocolate Brownie', 'price' => 22000, 'status' => 'cancelled'],
    ];
    $sort = request('sort');
    $direction = request('direction', 'asc');
@endphp
<x-layouts.admin title="UI kit">
    <x-admin.page-header title="Admin UI kit" :breadcrumbs="[['label' => 'Admin', 'url' => route('admin.dashboard')], ['label' => 'UI kit']]">
        <x-slot:actions>
            <x-link-button href="#">Add product</x-link-button>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="space-y-10">
        {{-- 4.45 + 4.44 filter bar and table --}}
        <section class="space-y-4" aria-labelledby="akit-table">
            <h2 id="akit-table" class="text-lg font-semibold">Filter bar and data table</h2>
            <x-admin.filter-bar :statuses="['all' => 'All', 'active' => 'Active', 'inactive' => 'Inactive', 'trashed' => 'Trashed']" :current="request('status', 'all')" placeholder="Search products...">
                <div class="w-48">
                    <label for="filter-category" class="sr-only">Category</label>
                    <x-form.select name="category" id="filter-category" :options="['pizza' => 'Pizza', 'sides' => 'Sides']" placeholder="All categories" />
                </div>
            </x-admin.filter-bar>

            <x-admin.table selectable :sort="$sort" :direction="$direction"
                :columns="[['label' => 'Name', 'sort' => 'name'], ['label' => 'Price', 'sort' => 'price'], ['label' => 'Status'], ['label' => 'Actions']]">
                <x-slot:bulk>
                    <button type="button" class="font-semibold text-cheese hover:underline">Hide</button>
                    <button type="button" class="font-semibold text-red-300 hover:underline">Move to trash</button>
                </x-slot:bulk>
                @foreach ($rows as $row)
                    <tr>
                        <td class="w-10 px-4 py-3">
                            <input type="checkbox" data-row-checkbox x-on:change="update()" aria-label="Select {{ $row['name'] }}" class="h-4 w-4 rounded border-stone-400 text-brand-600">
                        </td>
                        <td class="px-4 py-3 font-medium">{{ $row['name'] }}</td>
                        <td class="px-4 py-3"><x-money :amount="$row['price']" /></td>
                        <td class="px-4 py-3"><x-badge :variant="$row['status']">{{ ucfirst($row['status']) }}</x-badge></td>
                        <td class="px-4 py-3">
                            <div class="flex gap-3">
                                <a href="#" class="font-medium text-brand-700 hover:underline">Edit</a>
                                <button type="button" class="font-medium text-red-700 hover:underline"
                                    x-on:click="$dispatch('open-modal', 'delete-{{ $row['id'] }}')">Delete</button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-admin.table>

            @foreach ($rows as $row)
                <x-admin.confirm-delete :name="'delete-'.$row['id']" :title="'Move '.$row['name'].' to trash?'" />
            @endforeach

            @php
                $demoPage = new \Illuminate\Pagination\LengthAwarePaginator(
                    items: collect(range(1, 10)), total: 95, perPage: 10,
                    currentPage: (int) request('page', 1), options: ['path' => url()->current()],
                );
            @endphp
            {{ $demoPage->links() }}
        </section>

        {{-- 4.46 + 4.47 form --}}
        <section aria-labelledby="akit-form">
            <h2 id="akit-form" class="mb-3 text-lg font-semibold">Form layout and image upload</h2>
            <form method="POST" action="#" x-on:submit.prevent class="rounded-card border border-line bg-white px-6 pt-6 shadow-card">
                <x-admin.form-section title="Basics" description="Name and price shown on the menu.">
                    <x-form.input name="name" label="Name" required />
                    <x-form.input name="price" label="Price (taka)" type="number" step="0.01" help="Enter taka. It is stored in poisha." />
                    <div class="sm:col-span-2"><x-form.textarea name="description" label="Description" /></div>
                </x-admin.form-section>
                <x-admin.form-section title="Photo" description="JPEG, PNG or WebP.">
                    <div class="sm:col-span-2"><x-admin.image-upload name="image" label="Product image" help="Shown on cards and the product page." /></div>
                </x-admin.form-section>
                <x-admin.form-section title="Visibility">
                    <x-form.toggle name="is_published" label="Published on the site" :checked="true" />
                    <x-form.toggle name="is_available" label="Available to order now" :checked="true" />
                </x-admin.form-section>
                <x-admin.save-bar cancel="#" />
            </form>
        </section>

        {{-- 4.49 sortable list --}}
        <section aria-labelledby="akit-sort" x-data="{ order: [] }" x-on:sorted="order = $event.detail.ids.map((id, index) => id + ' -> sort ' + ((index + 1) * 10))">
            <h2 id="akit-sort" class="mb-3 text-lg font-semibold">Drag to reorder</h2>
            <x-admin.sortable-list label="Categories" class="max-w-md">
                @foreach (['Pizza', 'Pies', 'Sides', 'Pasta'] as $index => $name)
                    <li data-id="{{ $name }}" class="flex items-center gap-3 px-4 py-3">
                        <button type="button" data-handle aria-label="Drag {{ $name }} to reorder" class="cursor-grab text-muted hover:text-ink">
                            <x-icon name="bars-3" class="h-5 w-5" />
                        </button>
                        <span class="font-medium">{{ $name }}</span>
                    </li>
                @endforeach
            </x-admin.sortable-list>
            <p class="mt-3 text-sm text-muted" x-show="order.length" x-cloak>New order (sort in steps of 10): <span class="font-medium text-ink" x-text="order.join(', ')"></span></p>
        </section>
    </div>
</x-layouts.admin>

