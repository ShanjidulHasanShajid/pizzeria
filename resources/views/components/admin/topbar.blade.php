<header class="sticky top-0 z-30 flex items-center justify-between gap-4 border-b border-line bg-white px-4 py-3 sm:px-6 lg:px-8">
    <button type="button" class="-ml-2 p-2 text-ink lg:hidden" aria-label="Open admin menu" x-on:click="sidebar = true">
        <x-icon name="bars-3" class="h-6 w-6" />
    </button>

    <p class="hidden text-sm text-muted lg:block">Pizzeria admin</p>

    <div class="ml-auto flex items-center gap-3">
        <a href="{{ route('home') }}" target="_blank" rel="noopener" class="text-sm font-medium text-brand-700 hover:underline">View site</a>

        <x-dropdown>
            <x-slot:trigger>
                <button type="button" class="flex items-center gap-2 rounded-btn px-2 py-1.5 hover:bg-stone-100" aria-haspopup="true" x-bind:aria-expanded="open.toString()">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-600 text-sm font-bold text-white" aria-hidden="true">A</span>
                    <span class="hidden text-sm font-medium sm:block">Admin</span>
                    <x-icon name="chevron-down" class="h-4 w-4" />
                </button>
            </x-slot:trigger>
            <a href="#" role="menuitem" class="block px-4 py-2 text-sm hover:bg-cream">Profile</a>
            <span role="menuitem" aria-disabled="true" class="block cursor-not-allowed px-4 py-2 text-sm text-stone-500">Sign out (Phase 5)</span>
        </x-dropdown>
    </div>
</header>