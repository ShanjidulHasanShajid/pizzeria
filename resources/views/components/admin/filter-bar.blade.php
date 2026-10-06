@props(['statuses' => [], 'current' => 'all', 'placeholder' => 'Search...'])
<div class="space-y-3">
    @if ($statuses !== [])
        <nav aria-label="Filter by status" class="flex flex-wrap gap-2">
            @foreach ($statuses as $key => $label)
                <a href="{{ request()->fullUrlWithQuery(['status' => $key, 'page' => null]) }}" @if ($current === $key) aria-current="true" @endif
                    class="rounded-full px-3 py-1 text-sm font-medium {{ $current === $key ? 'bg-ink text-white' : 'bg-white text-muted ring-1 ring-line hover:text-ink' }}">{{ $label }}</a>
            @endforeach
        </nav>
    @endif

    <form method="GET" class="flex flex-wrap items-end gap-3 rounded-card border border-line bg-white p-4">
        <input type="hidden" name="status" value="{{ $current }}">
        <div class="min-w-48 flex-1">
            <label for="filter-q" class="sr-only">Search</label>
            <input id="filter-q" type="search" name="q" value="{{ request('q') }}" placeholder="{{ $placeholder }}" class="field">
        </div>
        {{ $slot }}
        <x-button type="submit">Filter</x-button>
        <a href="{{ url()->current() }}" class="py-2 text-sm text-muted underline hover:text-ink">Reset</a>
    </form>
</div>