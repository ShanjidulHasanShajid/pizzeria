@inject('placeholder', 'App\Modules\Shared\Presentation\Support\PlaceholderData')
<nav class="hidden border-b border-line bg-white lg:block" aria-label="Main">
    <div class="page-container relative">
        <ul class="flex items-center gap-1">
            @foreach ($placeholder->navItems() as $item)
                @php($active = request()->routeIs(...$item['patterns']))

                @if ($item['key'] === 'menu')
                    <li x-data="{ open: false }"
                        x-on:mouseenter="open = true" x-on:mouseleave="open = false"
                        x-on:keydown.escape="open = false"
                        x-on:focusout="if (! $el.contains($event.relatedTarget)) open = false">
                        <div class="flex items-center">
                            <a href="{{ route($item['route']) }}" @if ($active) aria-current="page" @endif
                                class="py-3 pl-4 text-sm font-semibold hover:text-brand-700 {{ $active ? 'text-brand-700' : 'text-ink' }}">{{ $item['label'] }}</a>
                            <button type="button" class="px-2 py-3 text-muted hover:text-brand-700" aria-label="Show menu categories"
                                x-on:click="open = ! open" x-bind:aria-expanded="open.toString()">
                                <x-icon name="chevron-down" class="h-4 w-4" />
                            </button>
                        </div>
                        <div x-show="open" x-cloak x-transition.opacity class="absolute inset-x-0 top-full z-30">
                            <x-storefront.mega-menu :groups="$placeholder->menuGroups()" />
                        </div>
                    </li>
                @elseif ($item['key'] === 'builder')
                    <li>
                        <a href="{{ route($item['route']) }}" class="mx-1 inline-block rounded-btn bg-brand-600 px-4 py-1.5 text-sm font-semibold text-white hover:bg-brand-700">{{ $item['label'] }}</a>
                    </li>
                @else
                    <li>
                        <a href="{{ route($item['route']) }}" @if ($active) aria-current="page" @endif
                            class="inline-block px-4 py-3 text-sm font-semibold hover:text-brand-700 {{ $active ? 'text-brand-700' : 'text-ink' }}">{{ $item['label'] }}</a>
                    </li>
                @endif
            @endforeach
        </ul>
    </div>
</nav>

