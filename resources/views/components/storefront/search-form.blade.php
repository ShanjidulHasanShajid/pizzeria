{{-- The search field with suggestions (resources/js/search-box.js). Works as a normal form without JavaScript. --}}
@props(['id', 'value' => ''])
<form action="{{ route('search') }}" method="GET" role="search" {{ $attributes->class('relative') }}
    x-data="searchBox(@js(route('search.suggestions')))" x-on:click.outside="close()" x-on:keydown.escape="close()">
    <label for="{{ $id }}" class="sr-only">Search the menu</label>
    <input id="{{ $id }}" type="search" name="q" value="{{ $value }}" maxlength="60" autocomplete="off" placeholder="Search pizza, pasta, desserts..." class="field pr-12"
        role="combobox" aria-autocomplete="list" aria-controls="{{ $id }}-list" aria-expanded="false"
        x-bind:aria-expanded="open.toString()" x-bind:aria-activedescendant="active >= 0 ? '{{ $id }}-option-' + active : null"
        x-on:input.debounce.300ms="load($event.target.value)" x-on:focus="open = items.length > 0"
        x-on:keydown.arrow-down.prevent="move(1)" x-on:keydown.arrow-up.prevent="move(-1)"
        x-on:keydown.enter="choose($event)">
    <button type="submit" class="absolute inset-y-0 right-0 px-3 text-muted hover:text-brand-700" aria-label="Search">
        <x-icon name="search" class="h-5 w-5" />
    </button>

    <ul id="{{ $id }}-list" role="listbox" aria-label="Suggestions" x-show="open" x-cloak
        class="absolute inset-x-0 top-full z-40 mt-1 overflow-hidden rounded-card border border-line bg-white shadow-pop">
        <template x-for="(item, index) in items" x-bind:key="item.url">
            <li role="option" x-bind:id="'{{ $id }}-option-' + index" x-bind:aria-selected="(index === active).toString()">
                <a x-bind:href="item.url" tabindex="-1" class="flex items-center gap-3 px-3 py-2 text-sm hover:bg-brand-50" x-bind:class="index === active ? 'bg-brand-50' : ''" x-on:mouseenter="active = index">
                    <img x-bind:src="item.image" alt="" class="h-10 w-10 rounded-lg object-cover">
                    <span class="flex-1 font-medium text-ink" x-text="item.name"></span>
                    <span class="text-muted" x-text="item.isSoldOut ? 'Sold out' : item.price"></span>
                </a>
            </li>
        </template>
    </ul>
</form>
