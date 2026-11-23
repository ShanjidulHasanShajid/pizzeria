@props(['text'])
<section class="bg-ink text-white" aria-label="Opening hours">
    <div class="page-container flex flex-col items-start justify-between gap-6 py-10 md:flex-row md:items-center">
        <div>
            <h2 class="font-display text-2xl font-bold">Opening hours</h2>
            <p class="mt-3 text-white/90">{{ $text }}</p>
        </div>
        <x-link-button :href="route('locations.index')" size="lg">Find a branch</x-link-button>
    </div>
</section>
