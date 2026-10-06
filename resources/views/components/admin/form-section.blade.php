@props(['title', 'description' => null])
<section class="grid gap-6 border-b border-line py-8 first:pt-0 lg:grid-cols-3">
    <div>
        <h2 class="text-base font-semibold">{{ $title }}</h2>
        @if ($description)
            <p class="mt-1 text-sm text-muted">{{ $description }}</p>
        @endif
    </div>
    <div class="grid gap-4 sm:grid-cols-2 lg:col-span-2">{{ $slot }}</div>
</section>