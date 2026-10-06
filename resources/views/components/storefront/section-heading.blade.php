@props(['title', 'href' => null, 'linkLabel' => 'View all'])
<div class="flex items-end justify-between gap-4">
    <h2 class="font-display text-2xl font-bold text-ink md:text-3xl">{{ $title }}</h2>
    @if ($href)
        <a href="{{ $href }}" class="inline-flex shrink-0 items-center gap-1 text-sm font-semibold text-brand-700 hover:underline">
            {{ $linkLabel }} <x-icon name="arrow-right" class="h-4 w-4" />
        </a>
    @endif
</div>