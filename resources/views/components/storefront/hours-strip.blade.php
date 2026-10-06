@props(['hours'])
<section class="bg-ink text-white" aria-label="Opening hours">
    <div class="page-container flex flex-col items-start justify-between gap-6 py-10 md:flex-row md:items-center">
        <div>
            <h2 class="font-display text-2xl font-bold">We are open</h2>
            <dl class="mt-3 space-y-1">
                @foreach ($hours as $days => $time)
                    <div class="flex gap-2"><dt class="font-semibold text-cheese">{{ $days }}:</dt><dd>{{ $time }}</dd></div>
                @endforeach
            </dl>
        </div>
        <x-link-button :href="route('locations.index')" size="lg">Find a branch</x-link-button>
    </div>
</section>