@props(['title', 'breadcrumbs' => []])
<div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div>
        @if ($breadcrumbs !== [])
            <x-breadcrumbs :items="$breadcrumbs" />
        @endif
        <h1 class="mt-1 font-display text-2xl font-bold md:text-3xl">{{ $title }}</h1>
    </div>
    @isset($actions)
        <div class="flex flex-wrap gap-2">{{ $actions }}</div>
    @endisset
</div>
