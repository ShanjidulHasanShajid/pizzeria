@props(['title', 'text' => ''])
<section class="page-container py-8">
    <div class="grid overflow-hidden rounded-card bg-accent-800 text-white md:grid-cols-2">
        <div class="p-8 md:p-12">
            <h2 class="font-display text-3xl font-bold md:text-4xl">{{ $title }}</h2>
            @if ($text !== '')
                <p class="mt-4 text-white/90">{{ $text }}</p>
            @endif
            <x-link-button :href="route('builder.show')" class="mt-6 bg-cheese !text-ink hover:bg-amber-300" size="lg">Start building</x-link-button>
        </div>
        <div class="min-h-48 bg-accent-900">
            <img src="{{ asset('images/placeholder.svg') }}" alt="" class="h-full w-full object-cover opacity-80">
        </div>
    </div>
</section>
