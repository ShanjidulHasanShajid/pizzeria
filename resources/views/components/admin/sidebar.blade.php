@php
    // [label, route name, gate]. A link shows only if the current user passes the gate.
    // A link is clickable only once a later phase defines the route.
    $groups = [
        ['label' => null, 'items' => [['Dashboard', 'admin.dashboard', 'access-admin'], ['Orders', 'admin.orders.index', 'manage-orders']]],
        ['label' => 'Catalog', 'items' => [
            ['Categories', 'admin.categories.index', 'manage-catalog'], ['Products', 'admin.products.index', 'manage-catalog'],
            ['Size Tiers', 'admin.size-tiers.index', 'manage-catalog'], ['Tags', 'admin.tags.index', 'manage-catalog'],
            ['Kitchen Stations', 'admin.kitchen-stations.index', 'manage-catalog'], ['Option Groups', 'admin.option-groups.index', 'manage-catalog'],
            ['Combos', 'admin.combos.index', 'manage-catalog'], ['Pizza Builder', 'admin.pizza-builder.edit', 'manage-catalog'],
        ]],
        ['label' => 'Content', 'items' => [
            ['Home', 'admin.home.edit', 'manage-content'], ['Banners', 'admin.banners.index', 'manage-content'], ['Gallery', 'admin.gallery.index', 'manage-content'],
            ['Navigation', 'admin.navigation.index', 'manage-content'], ['Pages', 'admin.pages.index', 'manage-content'],
            ['FAQs', 'admin.faqs.index', 'manage-content'], ['Locations', 'admin.locations.index', 'manage-content'],
        ]],
        ['label' => 'Sales', 'items' => [['Delivery Zones', 'admin.delivery-zones.index', 'manage-sales'], ['Coupons', 'admin.coupons.index', 'manage-sales']]],
        ['label' => 'People', 'items' => [['Customers', 'admin.customers.index', 'manage-customers'], ['Admin Users', 'admin.admin-users.index', 'manage-admin-users']]],
        ['label' => null, 'items' => [
            ['Reviews', 'admin.reviews.index', 'manage-content'], ['Messages', 'admin.messages.index', 'manage-content'],
            ['Settings', 'admin.settings.edit', 'manage-settings'], ['Trash', 'admin.trash.index', 'access-admin-sections'],
        ]],
    ];

    if (! app()->isProduction()) {
        $groups[] = ['label' => 'Development', 'items' => [['UI kit', 'admin.ui-kit', 'access-admin-sections']]];
    }

    // Keep only what this user may open, and drop groups that end up empty.
    $groups = collect($groups)
        ->map(fn (array $group): array => [
            'label' => $group['label'],
            'items' => array_values(array_filter($group['items'], fn (array $item): bool => Gate::allows($item[2]))),
        ])
        ->filter(fn (array $group): bool => $group['items'] !== [])
        ->all();
@endphp

<div x-show="sidebar" x-cloak class="fixed inset-0 z-40 bg-ink/60 lg:hidden" x-on:click="sidebar = false" aria-hidden="true"></div>

<aside class="fixed inset-y-0 left-0 z-50 w-64 shrink-0 overflow-y-auto bg-ink p-4 text-white transition-transform lg:sticky lg:top-0 lg:h-screen lg:translate-x-0"
    x-bind:class="sidebar ? 'translate-x-0' : '-translate-x-full'" aria-label="Admin sidebar">
    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 px-2 py-2">
        <img src="{{ asset('images/logo-mark.svg') }}" alt="" class="h-8 w-8">
        <span class="font-display text-lg font-bold">{{ config('app.name') }}</span>
    </a>

    <nav class="mt-4" aria-label="Admin">
        @foreach ($groups as $group)
            <div class="mt-5 first:mt-0">
                @if ($group['label'])
                    <p class="px-3 text-xs font-semibold uppercase tracking-wider text-white/60">{{ $group['label'] }}</p>
                @endif
                <ul class="mt-1 space-y-0.5">
                    @foreach ($group['items'] as [$label, $route])
                        @php
                            $live = Route::has($route);
                            $pattern = $route === 'admin.dashboard' ? $route : Str::beforeLast($route, '.').'.*';
                            $active = $live && request()->routeIs($pattern);
                        @endphp
                        <li>
                            @if ($live)
                                <a href="{{ route($route) }}" @if ($active) aria-current="page" @endif
                                    class="block rounded-lg px-3 py-2 text-sm {{ $active ? 'bg-white/15 font-semibold text-white' : 'text-white/85 hover:bg-white/10' }}">{{ $label }}</a>
                            @else
                                <span class="block cursor-not-allowed rounded-lg px-3 py-2 text-sm text-white/40" title="Available in a later phase">{{ $label }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </nav>
</aside>