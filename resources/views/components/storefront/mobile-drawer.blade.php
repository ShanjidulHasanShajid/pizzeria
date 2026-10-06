@inject('placeholder', 'App\Modules\Shared\Presentation\Support\PlaceholderData')
<div x-show="drawer" x-cloak class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true" aria-label="Site menu">
    <div class="absolute inset-0 bg-ink/60" x-on:click="drawer = false" aria-hidden="true"></div>

    <div x-show="drawer"
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
        x-trap.noscroll="drawer" x-on:keydown.escape.window="drawer = false"
        class="relative h-full w-80 max-w-[85%] overflow-y-auto bg-white p-5 shadow-pop">

        <div class="flex items-center justify-between">
            <span class="font-display text-xl font-bold text-brand-700">{{ config('app.name') }}</span>
            <button type="button" x-on:click="drawer = false" aria-label="Close menu" class="p-2 text-muted hover:text-ink">
                <x-icon name="x-mark" class="h-6 w-6" />
            </button>
        </div>

        <nav class="mt-6" aria-label="Mobile">
            <ul class="divide-y divide-line">
                @foreach ($placeholder->navItems() as $item)
                    @if ($item['key'] === 'menu')
                        <li x-data="{ open: false }">
                            <button type="button" class="flex w-full items-center justify-between py-3 text-left font-semibold"
                                x-on:click="open = ! open" x-bind:aria-expanded="open.toString()">
                                {{ $item['label'] }}
                                <x-icon name="chevron-down" class="h-5 w-5 transition" x-bind:class="open ? 'rotate-180' : ''" />
                            </button>
                            <ul x-show="open" x-collapse class="pb-3 pl-3">
                                @foreach ($placeholder->menuGroups() as $group)
                                    <li x-data="{ sub: false }">
                                        <div class="flex items-center justify-between">
                                            <a href="{{ route('categories.show', $group['slug']) }}" class="flex-1 py-2 text-sm font-medium">{{ $group['name'] }}</a>
                                            @if ($group['children'] !== [])
                                                <button type="button" class="p-2 text-muted" aria-label="Show {{ $group['name'] }} sub-categories"
                                                    x-on:click="sub = ! sub" x-bind:aria-expanded="sub.toString()">
                                                    <x-icon name="chevron-down" class="h-4 w-4 transition" x-bind:class="sub ? 'rotate-180' : ''" />
                                                </button>
                                            @endif
                                        </div>
                                        @if ($group['children'] !== [])
                                            <ul x-show="sub" x-collapse class="pl-3">
                                                @foreach ($group['children'] as $child)
                                                    <li><a href="{{ route('categories.show', $child['slug']) }}" class="block py-1.5 text-sm text-muted">{{ $child['name'] }}</a></li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    @else
                        <li><a href="{{ route($item['route']) }}" class="block py-3 font-semibold">{{ $item['label'] }}</a></li>
                    @endif
                @endforeach
            </ul>

            <ul class="mt-6 space-y-3 text-sm">
                <li><a href="{{ route('orders.track') }}" class="text-muted">Track order</a></li>
                <li><a href="{{ route('faq') }}" class="text-muted">Help</a></li>
                <li><a href="{{ url('/login') }}" class="text-muted">Sign in / Register</a></li>
            </ul>
        </nav>
    </div>
</div>

