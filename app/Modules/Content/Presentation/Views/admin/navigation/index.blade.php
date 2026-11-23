{{-- $items: list<NavItemView>, in order. --}}
@use('App\Modules\Content\Domain\ValueObjects\NavItem')
@use('App\Modules\Content\Presentation\Support\NavLinks')
<x-layouts.admin title="Navigation">
    <x-admin.page-header title="Navigation" :breadcrumbs="[['label' => 'Admin', 'url' => route('admin.dashboard')], ['label' => 'Navigation']]" />

    <p class="mb-6 max-w-3xl text-sm text-muted">The entries of the navbar. Drag to change the order. The pages are fixed; a highlighted entry is shown as a button.</p>

    <div class="max-w-4xl" x-data="{ state: '' }"
        x-on:sorted="state = 'saving'; axios.post(@js(route('admin.navigation.reorder')), { ids: $event.detail.ids }).then(() => state = 'saved').catch(() => state = 'failed')">
        <x-admin.sortable-list label="Navigation">
            @foreach ($items as $item)
                @php
                    $key = $item->route->value;
                    // All rows have a field "label": show old input and the error only in the row that was sent.
                    $wasSent = old('nav_item') === $key;
                    $hasError = $wasSent && $errors->has('label');
                @endphp
                <li data-id="{{ $key }}" class="flex flex-wrap items-center gap-3 px-4 py-3">
                    <button type="button" data-handle aria-label="Drag {{ $item->label }} to reorder" class="cursor-grab text-muted hover:text-ink">
                        <x-icon name="bars-3" class="h-5 w-5" />
                    </button>
                    <form method="POST" action="{{ route('admin.navigation.update', $key) }}" class="flex min-w-56 flex-1 flex-wrap items-center gap-2" novalidate>
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="nav_item" value="{{ $key }}">
                        <label for="label-{{ $key }}" class="sr-only">Label for {{ $item->route->defaultLabel() }}</label>
                        <input id="label-{{ $key }}" name="label" value="{{ $wasSent ? old('label') : $item->label }}" maxlength="{{ NavItem::LABEL_MAX_LENGTH }}"
                            @if ($hasError) aria-invalid="true" aria-describedby="label-{{ $key }}-error" @endif
                            class="field {{ $hasError ? 'field-invalid' : '' }} max-w-48">
                        <x-button type="submit" size="sm" variant="outline">Save</x-button>
                        <span class="text-xs text-muted">{{ route(NavLinks::routeName($item->route), absolute: false) }}</span>
                        @if ($hasError)
                            <p id="label-{{ $key }}-error" class="w-full text-sm font-medium text-red-700" role="alert">{{ $errors->first('label') }}</p>
                        @endif
                    </form>
                    @if ($item->isHighlighted)
                        <x-badge variant="hot">Highlighted</x-badge>
                    @endif
                    <x-badge :variant="$item->isVisible ? 'veg' : 'neutral'">{{ $item->isVisible ? 'Shown' : 'Hidden' }}</x-badge>
                    <form method="POST" action="{{ route('admin.navigation.highlight', $key) }}">
                        @csrf
                        @method('PATCH')
                        <x-button type="submit" size="sm" variant="outline">{{ $item->isHighlighted ? 'Remove highlight' : 'Highlight' }}</x-button>
                    </form>
                    <form method="POST" action="{{ route('admin.navigation.visibility', $key) }}">
                        @csrf
                        @method('PATCH')
                        <x-button type="submit" size="sm" variant="outline">{{ $item->isVisible ? 'Hide' : 'Show' }}</x-button>
                    </form>
                </li>
            @endforeach
        </x-admin.sortable-list>

        <p class="mt-3 text-sm" role="status" aria-live="polite">
            <span x-show="state === 'saving'" x-cloak class="text-muted">Saving the new order...</span>
            <span x-show="state === 'saved'" x-cloak class="text-accent-700">New order saved.</span>
            <span x-show="state === 'failed'" x-cloak class="text-red-700">The new order could not be saved. Reload the page and try again.</span>
        </p>
    </div>
</x-layouts.admin>
