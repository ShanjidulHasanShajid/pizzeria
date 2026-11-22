{{--
    $categories: list<AdminCategoryRow>. $filter: AdminCategoryFilter. $imageUrl: path => URL.
    Without a filter the list is a tree: one draggable list for the top level, and one
    draggable list per parent for its sub-categories (a drag never moves an item to another parent).
--}}
@php
    $topLevel = array_values(array_filter($categories, fn ($row) => $row->parentId === null));
    $topLevelIds = array_map(fn ($row) => $row->id, $topLevel);
    $childrenOf = [];
    $orphans = [];

    foreach ($categories as $row) {
        if ($row->parentId === null) {
            continue;
        }

        if (in_array($row->parentId, $topLevelIds, true)) {
            $childrenOf[$row->parentId][] = $row;
        } else {
            $orphans[] = $row;
        }
    }
@endphp
<x-layouts.admin title="Categories">
    <x-admin.page-header title="Categories" :breadcrumbs="[['label' => 'Admin', 'url' => route('admin.dashboard')], ['label' => 'Categories']]">
        <x-slot:actions>
            <x-link-button :href="route('admin.categories.create')">Add category</x-link-button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.filter-bar :statuses="['all' => 'All', 'active' => 'Active', 'inactive' => 'Inactive', 'trashed' => 'Trash']" :current="$filter->status" placeholder="Search name or slug..." />

    <div class="mt-6">
        @if ($categories === [])
            <x-empty-state title="No categories here" :message="$filter->isTree() ? 'Add the first category, for example Pizza.' : 'Nothing matches this search or filter.'" />
        @elseif ($filter->isTree())
            <p class="mb-4 max-w-3xl text-sm text-muted">Drag by the handle to change the order in the menu. Sub-categories are ordered inside their parent. To move a category to another parent, edit it.</p>

            <section class="max-w-5xl" x-data="{ state: '' }"
                x-on:sorted="state = 'saving'; axios.post(@js(route('admin.categories.reorder')), { ids: $event.detail.ids }).then(() => state = 'saved').catch(() => state = 'failed')">
                <h2 class="mb-2 text-sm font-semibold uppercase tracking-wide text-muted">Top-level categories</h2>
                <x-admin.sortable-list label="Top-level categories">
                    @foreach ($topLevel as $row)
                        <li data-id="{{ $row->id }}" class="flex flex-wrap items-center gap-3 px-4 py-3">
                            <button type="button" data-handle aria-label="Drag {{ $row->name }} to reorder" class="cursor-grab text-muted hover:text-ink">
                                <x-icon name="bars-3" class="h-5 w-5" />
                            </button>
                            @include('catalog::admin.categories._summary', ['row' => $row])
                            @if (isset($childrenOf[$row->id]))
                                <span class="text-xs text-muted">{{ count($childrenOf[$row->id]) }} {{ Str::plural('sub-category', count($childrenOf[$row->id])) }}</span>
                            @endif
                            @include('catalog::admin.categories._actions', ['row' => $row, 'canDelete' => $row->productCount === 0 && ! isset($childrenOf[$row->id])])
                        </li>
                    @endforeach
                </x-admin.sortable-list>

                @foreach ($topLevel as $parent)
                    @if (isset($childrenOf[$parent->id]))
                        <h2 class="mb-2 mt-8 text-sm font-semibold uppercase tracking-wide text-muted">Sub-categories of {{ $parent->name }}</h2>
                        <x-admin.sortable-list :label="'Sub-categories of '.$parent->name" class="ml-6">
                            @foreach ($childrenOf[$parent->id] as $row)
                                <li data-id="{{ $row->id }}" class="flex flex-wrap items-center gap-3 px-4 py-3">
                                    <button type="button" data-handle aria-label="Drag {{ $row->name }} to reorder" class="cursor-grab text-muted hover:text-ink">
                                        <x-icon name="bars-3" class="h-5 w-5" />
                                    </button>
                                    @include('catalog::admin.categories._summary', ['row' => $row])
                                    @include('catalog::admin.categories._actions', ['row' => $row, 'canDelete' => $row->productCount === 0])
                                </li>
                            @endforeach
                        </x-admin.sortable-list>
                    @endif
                @endforeach

                <p class="mt-3 text-sm" role="status" aria-live="polite">
                    <span x-show="state === 'saving'" x-cloak class="text-muted">Saving the new order...</span>
                    <span x-show="state === 'saved'" x-cloak class="text-accent-700">New order saved.</span>
                    <span x-show="state === 'failed'" x-cloak class="text-red-700">The new order could not be saved. Reload the page and try again.</span>
                </p>
            </section>

            @if ($orphans !== [])
                <section class="mt-8 max-w-5xl">
                    <h2 class="mb-2 text-sm font-semibold uppercase tracking-wide text-muted">Parent in the Trash</h2>
                    <p class="mb-2 text-sm text-muted">These sub-categories belong to a parent that is in the Trash. Restore the parent, or edit them and choose another parent. The shop does not show them.</p>
                    <ul class="divide-y divide-line rounded-card border border-line bg-white">
                        @foreach ($orphans as $row)
                            <li class="flex flex-wrap items-center gap-3 px-4 py-3">
                                @include('catalog::admin.categories._summary', ['row' => $row])
                                @include('catalog::admin.categories._actions', ['row' => $row, 'canDelete' => $row->productCount === 0])
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        @else
            <ul class="max-w-5xl divide-y divide-line rounded-card border border-line bg-white">
                @foreach ($categories as $row)
                    <li class="flex flex-wrap items-center gap-3 px-4 py-3">
                        @include('catalog::admin.categories._summary', ['row' => $row])
                        @if ($row->parentName)
                            <span class="text-xs text-muted">in {{ $row->parentName }}</span>
                        @endif
                        @include('catalog::admin.categories._actions', ['row' => $row, 'canDelete' => $row->productCount === 0])
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    {{-- Confirm dialogs, outside the lists so dragging never moves them. --}}
    @foreach ($categories as $row)
        @if ($row->isDeleted)
            @can('force-delete')
                <x-modal :name="'force-delete-category-'.$row->id" :title="'Delete '.$row->name.' forever?'">
                    <p class="text-sm text-muted">The category and its image are deleted for good. This cannot be undone.</p>
                    <form method="POST" action="{{ route('admin.categories.force-destroy', $row->id) }}" class="mt-6 flex justify-end gap-3">
                        @csrf
                        @method('DELETE')
                        <x-button variant="outline" x-on:click="$dispatch('close-modal')">Cancel</x-button>
                        <x-button type="submit" variant="danger">Delete forever</x-button>
                    </form>
                </x-modal>
            @endcan
        @else
            <x-admin.confirm-delete :name="'delete-category-'.$row->id" :action="route('admin.categories.destroy', $row->id)"
                :title="'Move '.$row->name.' to the trash?'" />
        @endif
    @endforeach
</x-layouts.admin>
