<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>UI kit (development only)</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-cream font-sans text-ink" x-data>
<main class="page-container space-y-12 py-10">
    <div>
        <h1 class="font-display text-3xl font-bold">UI kit</h1>
        <p class="mt-1 text-muted">Every shared component, on one page. Development only.</p>
    </div>

    {{-- 4.5 Buttons --}}
    <section class="space-y-3" aria-labelledby="kit-buttons">
        <h2 id="kit-buttons" class="text-lg font-semibold">Buttons</h2>
        <div class="flex flex-wrap items-center gap-3">
            <x-button>Primary</x-button>
            <x-button variant="secondary">Secondary</x-button>
            <x-button variant="outline">Outline</x-button>
            <x-button variant="danger">Danger</x-button>
            <x-button disabled>Disabled</x-button>
            <x-button :loading="true">Saving</x-button>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <x-button size="sm">Small</x-button>
            <x-button size="md">Medium</x-button>
            <x-button size="lg">Large</x-button>
            <x-button><x-icon name="shopping-bag" class="h-5 w-5" /> With icon</x-button>
        </div>
    </section>

    {{-- 4.6 Link buttons --}}
    <section class="space-y-3" aria-labelledby="kit-links">
        <h2 id="kit-links" class="text-lg font-semibold">Link buttons</h2>
        <div class="flex flex-wrap gap-3">
            <x-link-button href="#">Primary link</x-link-button>
            <x-link-button href="#" variant="outline">Outline link</x-link-button>
            <x-link-button href="#" variant="secondary" size="lg">Large link</x-link-button>
        </div>
    </section>

    {{-- 4.7 Badges --}}
    <section class="space-y-3" aria-labelledby="kit-badges">
        <h2 id="kit-badges" class="text-lg font-semibold">Badges</h2>
        <div class="flex flex-wrap gap-2">
            @foreach (['neutral', 'new', 'hot', 'veg', 'spicy', 'lead', 'soldout', 'pending', 'confirmed', 'preparing', 'ready', 'delivered', 'cancelled'] as $variant)
                <x-badge :variant="$variant">{{ ucfirst($variant) }}</x-badge>
            @endforeach
        </div>
    </section>

    {{-- 4.8 Form fields --}}
    <section class="space-y-4" aria-labelledby="kit-forms">
        <h2 id="kit-forms" class="text-lg font-semibold">Form fields</h2>
        <form class="grid max-w-2xl gap-4 sm:grid-cols-2" x-on:submit.prevent>
            <x-form.input name="full_name" label="Full name" required help="As it should appear on the order." />
            <x-form.input name="phone" label="Mobile number" type="tel" placeholder="01XXXXXXXXX" />
            <x-form.select name="area" label="Delivery area" :options="['gulshan' => 'Gulshan', 'banani' => 'Banani', 'dhanmondi' => 'Dhanmondi']" placeholder="Choose an area" />
            <x-form.textarea name="notes" label="Order notes" class="sm:col-span-2" />
            <div class="flex flex-wrap gap-6 sm:col-span-2">
                <x-form.checkbox name="terms" label="I accept the terms" />
                <x-form.radio name="type" value="delivery" label="Delivery" :checked="true" />
                <x-form.radio name="type" value="pickup" label="Pickup" />
                <x-form.toggle name="is_available" label="Available" :checked="true" />
            </div>
        </form>
        <p class="text-sm text-muted">Error state (the field below was given a fake error with <code>$errors</code>):</p>
        @php
            $demoErrors = new \Illuminate\Support\ViewErrorBag;
            $demoErrors->put('default', new \Illuminate\Support\MessageBag(['email' => 'Enter a valid email address.']));
        @endphp
        <div class="max-w-md">
            @include('shared::dev.partials.error-demo', ['errors' => $demoErrors])
        </div>
    </section>

    {{-- 4.9 Money --}}
    <section class="space-y-3" aria-labelledby="kit-money">
        <h2 id="kit-money" class="text-lg font-semibold">Money (amounts are poisha)</h2>
        <div class="flex flex-wrap items-baseline gap-6">
            <x-money :amount="59000" />
            <x-money :amount="125050" />
            <x-money :amount="49000" :compare-at="59000" />
            <x-money :amount="79000" from />
        </div>
    </section>

    {{-- 4.10 Card --}}
    <section class="space-y-3" aria-labelledby="kit-card">
        <h2 id="kit-card" class="text-lg font-semibold">Card</h2>
        <x-card class="max-w-sm">
            <h3 class="font-semibold">A card</h3>
            <p class="mt-1 text-sm text-muted">White surface, soft border and shadow.</p>
        </x-card>
    </section>

    {{-- 4.11 Modal --}}
    <section class="space-y-3" aria-labelledby="kit-modal">
        <h2 id="kit-modal" class="text-lg font-semibold">Modal</h2>
        <x-button variant="outline" x-on:click="$dispatch('open-modal', 'demo')">Open modal</x-button>
        <x-modal name="demo" title="Hello from the modal">
            <p class="text-sm text-muted">Press Escape, click the dark area or use the X to close. Focus stays inside while it is open.</p>
        </x-modal>
    </section>

    {{-- 4.12 Dropdown --}}
    <section class="space-y-3" aria-labelledby="kit-dropdown">
        <h2 id="kit-dropdown" class="text-lg font-semibold">Dropdown</h2>
        <x-dropdown align="left">
            <x-slot:trigger>
                <button type="button" class="btn btn-outline btn-md" aria-haspopup="true" x-bind:aria-expanded="open.toString()">
                    Account <x-icon name="chevron-down" class="h-4 w-4" />
                </button>
            </x-slot:trigger>
            <a href="#" role="menuitem" class="block px-4 py-2 text-sm hover:bg-cream">My orders</a>
            <a href="#" role="menuitem" class="block px-4 py-2 text-sm hover:bg-cream">Profile</a>
        </x-dropdown>
    </section>

    {{-- 4.13 Tabs --}}
    <section class="space-y-3" aria-labelledby="kit-tabs">
        <h2 id="kit-tabs" class="text-lg font-semibold">Tabs</h2>
        <x-tabs :tabs="['pizza' => 'Pizza', 'sides' => 'Sides', 'desserts' => 'Desserts']">
            <div x-show="tab === 'pizza'" role="tabpanel">Pizza panel</div>
            <div x-show="tab === 'sides'" x-cloak role="tabpanel">Sides panel</div>
            <div x-show="tab === 'desserts'" x-cloak role="tabpanel">Desserts panel</div>
        </x-tabs>
    </section>

    {{-- 4.14 Alerts --}}
    <section class="max-w-xl space-y-3" aria-labelledby="kit-alerts">
        <h2 id="kit-alerts" class="text-lg font-semibold">Alerts</h2>
        <x-alert type="success" dismissible>Your order was placed.</x-alert>
        <x-alert type="info">We deliver to Gulshan, Banani and Dhanmondi.</x-alert>
        <x-alert type="warning">This item is almost sold out.</x-alert>
        <x-alert type="error">The payment failed.</x-alert>
    </section>

    {{-- 4.15 Pagination --}}
    <section class="space-y-3" aria-labelledby="kit-pagination">
        <h2 id="kit-pagination" class="text-lg font-semibold">Pagination</h2>
        @php
            $demoPage = new \Illuminate\Pagination\LengthAwarePaginator(
                items: collect(range(1, 10)),
                total: 95,
                perPage: 10,
                currentPage: (int) request('page', 3),
                options: ['path' => url()->current()],
            );
        @endphp
        {{ $demoPage->links() }}
    </section>

    {{-- 4.16 Empty state --}}
    <section class="max-w-xl space-y-3" aria-labelledby="kit-empty">
        <h2 id="kit-empty" class="text-lg font-semibold">Empty state</h2>
        <x-empty-state title="Your cart is empty" message="Add something delicious from the menu.">
            <x-slot:action>
                <x-link-button href="#">Browse the menu</x-link-button>
            </x-slot:action>
        </x-empty-state>
    </section>

    {{-- 4.17 Breadcrumbs --}}
    <section class="space-y-3" aria-labelledby="kit-crumbs">
        <h2 id="kit-crumbs" class="text-lg font-semibold">Breadcrumbs</h2>
        <x-breadcrumbs :items="[['label' => 'Home', 'url' => '#'], ['label' => 'Menu', 'url' => '#'], ['label' => 'Margherita']]" />
    </section>

    {{-- 4.26 Product cards (needs the layout routes from 4.40 to render links) --}}
    <section class="space-y-3" aria-labelledby="kit-cards">
        <h2 id="kit-cards" class="text-lg font-semibold">Product cards</h2>
        @inject('placeholder', 'App\Modules\Shared\Presentation\Support\PlaceholderData')
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-storefront.product-card :product="$placeholder->products(1, 3)[0]" />
            <x-storefront.product-card :product="$placeholder->products(1, 0)[0]" />
            <x-storefront.product-card :product="$placeholder->combos()[0]" />
            <x-storefront.product-card :product="$placeholder->products(1, 9)[0]" />
        </div>
    </section>

    {{-- END OF KIT --}}
</main>
@livewireScripts
</body>
</html>