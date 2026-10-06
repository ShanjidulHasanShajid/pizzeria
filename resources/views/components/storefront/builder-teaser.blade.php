<section class="page-container py-8">
    <div class="grid overflow-hidden rounded-card bg-accent-800 text-white md:grid-cols-2">
        <div class="p-8 md:p-12">
            <p class="text-sm font-semibold uppercase tracking-widest text-cheese">Build your own</p>
            <h2 class="mt-2 font-display text-3xl font-bold md:text-4xl">Design your perfect pizza</h2>
            <ol class="mt-4 space-y-1 text-white/90">
                <li>1. Pick a size</li>
                <li>2. Choose your crust and sauce</li>
                <li>3. Add your favourite toppings</li>
            </ol>
            <p class="mt-3 text-sm text-white/80">Watch the price update as you build.</p>
            <x-link-button :href="route('builder.show')" class="mt-6 bg-cheese !text-ink hover:bg-amber-300" size="lg">Start building</x-link-button>
        </div>
        <div class="min-h-48 bg-accent-900">
            <img src="{{ asset('images/placeholder.svg') }}" alt="" class="h-full w-full object-cover opacity-80">
        </div>
    </div>
</section>