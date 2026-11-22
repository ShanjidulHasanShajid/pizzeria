{{--
    The buttons of one category row. Used by the tree and by the flat list.
    $row: AdminCategoryRow. $canDelete: false greys out Delete (the server checks again anyway).
--}}
<div class="flex flex-wrap items-center gap-2">
    @if ($row->isDeleted)
        <form method="POST" action="{{ route('admin.categories.restore', $row->id) }}">
            @csrf
            <x-button type="submit" size="sm" variant="outline">Restore</x-button>
        </form>
        @can('force-delete')
            <x-button size="sm" variant="danger" x-on:click="$dispatch('open-modal', 'force-delete-category-{{ $row->id }}')">Delete forever</x-button>
        @endcan
    @else
        <form method="POST" action="{{ route('admin.categories.toggle-active', $row->id) }}">
            @csrf
            @method('PATCH')
            <x-button type="submit" size="sm" variant="outline">{{ $row->isActive ? 'Switch off' : 'Switch on' }}</x-button>
        </form>
        <form method="POST" action="{{ route('admin.categories.toggle-featured', $row->id) }}">
            @csrf
            @method('PATCH')
            <x-button type="submit" size="sm" variant="outline">{{ $row->isFeatured ? 'Unfeature' : 'Feature' }}</x-button>
        </form>
        <x-link-button :href="route('admin.categories.edit', $row->id)" size="sm" variant="outline">Edit</x-link-button>
        @if ($canDelete)
            <x-button size="sm" variant="danger" x-on:click="$dispatch('open-modal', 'delete-category-{{ $row->id }}')">Delete</x-button>
        @else
            <x-button size="sm" variant="danger" disabled title="Has products or sub-categories">Delete</x-button>
        @endif
    @endif
</div>
