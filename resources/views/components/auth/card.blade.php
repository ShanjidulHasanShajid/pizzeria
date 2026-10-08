@props(['title', 'subtitle' => null])
<div class="page-container py-10 sm:py-16">
    <div class="mx-auto w-full max-w-md">
        <x-card class="sm:p-8">
            <h1 class="font-display text-2xl font-bold">{{ $title }}</h1>
            @if ($subtitle)
                <p class="mt-1 text-sm text-muted">{{ $subtitle }}</p>
            @endif
            <div class="mt-6">{{ $slot }}</div>
        </x-card>
    </div>
</div>