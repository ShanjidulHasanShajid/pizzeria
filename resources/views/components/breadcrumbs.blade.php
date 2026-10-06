@props(['items'])
<nav aria-label="Breadcrumb" {{ $attributes }}>
    <ol class="flex flex-wrap items-center gap-1 text-sm text-muted">
        @foreach ($items as $item)
            <li class="flex items-center gap-1">
                @if (! $loop->first)
                    <x-icon name="chevron-right" class="h-4 w-4 text-stone-400" />
                @endif
                @if (! $loop->last && isset($item['url']))
                    <a href="{{ $item['url'] }}" class="hover:text-brand-700 hover:underline">{{ $item['label'] }}</a>
                @else
                    <span aria-current="page" class="font-medium text-ink">{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>