{{-- $items: list<NavItemView>. $groups: list<CategoryNode>, the mega menu. --}}
@props(['items', 'groups'])
@use('App\Modules\Content\Domain\Enums\NavRoute')
@use('App\Modules\Content\Presentation\Support\NavLinks')
<nav class="hidden border-b border-line bg-white lg:block" aria-label="Main">
    <div class="page-container relative">
        <ul class="flex items-center gap-1">
            @foreach ($items as $item)
                @php
                    $url = route(NavLinks::routeName($item->route));
                    $active = request()->routeIs(...NavLinks::activePatterns($item->route));
                    $class = $item->isHighlighted
                        ? 'mx-1 inline-block rounded-btn bg-brand-600 px-4 py-1.5 text-sm font-semibold text-white hover:bg-brand-700'
                        : 'inline-block px-4 py-3 text-sm font-semibold hover:text-brand-700 '.($active ? 'text-brand-700' : 'text-ink');
                @endphp

                @if ($item->route === NavRoute::Menu && $groups !== [])
                    <li x-data="{ open: false }"
                        x-on:mouseenter="open = true" x-on:mouseleave="open = false"
                        x-on:keydown.escape="open = false"
                        x-on:focusout="if (! $el.contains($event.relatedTarget)) open = false">
                        <div class="flex items-center">
                            <a href="{{ $url }}" @if ($active) aria-current="page" @endif class="{{ $class }}">{{ $item->label }}</a>
                            <button type="button" class="-ml-2 px-2 py-3 text-muted hover:text-brand-700" aria-label="Show menu categories"
                                x-on:click="open = ! open" x-bind:aria-expanded="open.toString()">
                                <x-icon name="chevron-down" class="h-4 w-4" />
                            </button>
                        </div>
                        <div x-show="open" x-cloak x-transition.opacity class="absolute inset-x-0 top-full z-30">
                            <x-storefront.mega-menu :groups="$groups" />
                        </div>
                    </li>
                @else
                    <li>
                        <a href="{{ $url }}" @if ($active) aria-current="page" @endif class="{{ $class }}">{{ $item->label }}</a>
                    </li>
                @endif
            @endforeach
        </ul>
    </div>
</nav>
