{{-- $type: ContentType. $items: list<ContentItemView>, in sort order. $imageUrl: stored path => URL. --}}
<x-layouts.admin :title="$type->label()">
    <x-admin.page-header :title="$type->label()" :breadcrumbs="[['label' => 'Admin', 'url' => route('admin.dashboard')], ['label' => $type->label()]]">
        <x-slot:actions>
            <x-link-button :href="route('admin.'.$type->value.'.create')">Add {{ Str::lower($type->singular()) }}</x-link-button>
        </x-slot:actions>
    </x-admin.page-header>

    @if ($type->value === 'gallery')
        <form method="POST" action="{{ route('admin.gallery.upload') }}" enctype="multipart/form-data" class="mb-6 max-w-3xl rounded-card border border-dashed border-stone-300 bg-white p-4">
            @csrf
            <label for="gallery-images" class="mb-1 block text-sm font-medium text-ink">Add several photos</label>
            <input id="gallery-images" type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp" class="block w-full text-sm">
            <p class="mt-1 text-xs text-muted">JPG, PNG or WebP, at least 400 pixels wide, at most 2 MB each, up to 5 at once.</p>
            <x-form.error name="images" />
            @foreach ($errors->get('images.*') as $messages)
                <p class="mt-1 text-sm font-medium text-red-700" role="alert">{{ $messages[0] }}</p>
            @endforeach
            <x-button type="submit" size="sm" class="mt-3">Upload</x-button>
        </form>
    @endif

    @if ($items === [])
        <x-empty-state title="Nothing here yet" message="The home page leaves this section out." />
    @else
        <div class="max-w-4xl" x-data="{ state: '' }"
            x-on:sorted="state = 'saving'; axios.post(@js(route('admin.'.$type->value.'.reorder')), { ids: $event.detail.ids }).then(() => state = 'saved').catch(() => state = 'failed')">
            <x-admin.sortable-list :label="$type->label()">
                @foreach ($items as $item)
                    @php
                        $title = $item->value('title') ?: ($item->value('caption') ?: $type->singular().' '.$loop->iteration);
                        $image = $imageUrl($item->value('image'));
                    @endphp
                    <li data-id="{{ $item->id }}" class="flex flex-wrap items-center gap-3 px-4 py-3">
                        <button type="button" data-handle aria-label="Drag {{ $title }} to reorder" class="cursor-grab text-muted hover:text-ink">
                            <x-icon name="bars-3" class="h-5 w-5" />
                        </button>
                        @if ($image)
                            <img src="{{ $image }}" alt="" class="h-12 w-20 shrink-0 rounded-lg border border-line object-cover">
                        @elseif ($item->value('icon') !== '')
                            <x-icon :name="$item->value('icon')" class="h-6 w-6 text-brand-700" />
                        @endif
                        <span class="min-w-32 flex-1">
                            <span class="block font-medium">{{ $title }}</span>
                            @if ($item->startsAt || $item->endsAt)
                                <span class="block text-xs text-muted">
                                    {{ $item->startsAt?->format('j M Y, g:i A') ?? 'Now' }} to {{ $item->endsAt?->format('j M Y, g:i A') ?? 'no end' }}
                                </span>
                            @endif
                        </span>
                        @switch($item->state)
                            @case('visible')
                                <x-badge variant="veg">Visible</x-badge>
                                @break

                            @case('scheduled')
                                <x-badge variant="lead">Scheduled</x-badge>
                                @break

                            @case('expired')
                                <x-badge>Expired</x-badge>
                                @break

                            @default
                                <x-badge variant="soldout">Switched off</x-badge>
                        @endswitch
                        <form method="POST" action="{{ route('admin.'.$type->value.'.toggle', $item->id) }}">
                            @csrf
                            @method('PATCH')
                            <x-button type="submit" size="sm" variant="outline">{{ $item->isActive ? 'Switch off' : 'Switch on' }}</x-button>
                        </form>
                        <x-link-button :href="route('admin.'.$type->value.'.edit', $item->id)" size="sm" variant="outline">Edit</x-link-button>
                        <x-button size="sm" variant="danger" x-on:click="$dispatch('open-modal', 'delete-item-{{ $item->id }}')">Delete</x-button>
                    </li>
                @endforeach
            </x-admin.sortable-list>

            <p class="mt-3 text-sm" role="status" aria-live="polite">
                <span x-show="state === 'saving'" x-cloak class="text-muted">Saving the new order...</span>
                <span x-show="state === 'saved'" x-cloak class="text-accent-700">New order saved.</span>
                <span x-show="state === 'failed'" x-cloak class="text-red-700">The new order could not be saved. Reload the page and try again.</span>
            </p>
        </div>

        @foreach ($items as $item)
            <x-admin.confirm-delete :name="'delete-item-'.$item->id" :action="route('admin.'.$type->value.'.destroy', $item->id)" />
        @endforeach
    @endif
</x-layouts.admin>
