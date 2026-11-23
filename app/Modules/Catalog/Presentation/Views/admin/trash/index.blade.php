{{--
    $items: list of TrashedCatalogItem for the open tab, newest deletion first.
    $tabs: tab key => label with count. $type: the open tab. $kinds: TrashKinds::KINDS.
--}}
<x-layouts.admin title="Trash">
    <x-admin.page-header title="Trash" :breadcrumbs="[['label' => 'Admin', 'url' => route('admin.dashboard')], ['label' => 'Trash']]" />

    <p class="text-sm text-muted">Deleted catalog items and options wait here. Restore puts an item back where it was. Super admins can also remove an item for good.</p>

    <nav class="mt-4 flex gap-1 overflow-x-auto border-b border-line" aria-label="Item types">
        @foreach ($tabs as $key => $label)
            <a href="{{ route('admin.trash.index', $key === 'all' ? [] : ['type' => $key]) }}" @if ($key === $type) aria-current="page" @endif
                class="whitespace-nowrap border-b-2 px-4 py-2.5 text-sm font-semibold {{ $key === $type ? 'border-brand-600 text-brand-700' : 'border-transparent text-muted hover:text-ink' }}">{{ $label }}</a>
        @endforeach
    </nav>

    <div class="mt-4">
        @if ($items === [])
            <x-empty-state icon="trash" title="Nothing here" message="Items you delete from the catalog lists show up here." />
        @else
            <x-admin.table :columns="[['label' => 'Name'], ['label' => 'Type'], ['label' => 'Deleted'], ['label' => 'Actions']]">
                @foreach ($items as $item)
                    @php($kind = $kinds[$item->kind])
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $item->name }}</td>
                        <td class="px-4 py-3"><x-badge>{{ $kind['one'] }}</x-badge></td>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $item->deletedAt->format('j M Y, g:i A') }}</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <form method="POST" action="{{ route($kind['route'].'.restore', $item->id) }}">
                                    @csrf
                                    <x-button type="submit" size="sm" variant="outline">Restore</x-button>
                                </form>
                                @can('force-delete')
                                    <x-button size="sm" variant="danger" x-on:click="$dispatch('open-modal', 'force-delete-{{ $item->kind }}-{{ $item->id }}')">Delete forever</x-button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-admin.table>
        @endif
    </div>

    @can('force-delete')
        @foreach ($items as $item)
            <x-modal :name="'force-delete-'.$item->kind.'-'.$item->id" :title="'Delete '.$item->name.' forever?'">
                <p class="text-sm text-muted">{{ $kinds[$item->kind]['forever'] }} This cannot be undone.</p>
                <form method="POST" action="{{ route($kinds[$item->kind]['route'].'.force-destroy', $item->id) }}" class="mt-6 flex justify-end gap-3">
                    @csrf
                    @method('DELETE')
                    <x-button variant="outline" x-on:click="$dispatch('close-modal')">Cancel</x-button>
                    <x-button type="submit" variant="danger">Delete forever</x-button>
                </form>
            </x-modal>
        @endforeach
    @endcan
</x-layouts.admin>
