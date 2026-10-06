@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-muted">Showing {{ $paginator->firstItem() }} to {{ $paginator->lastItem() }} of {{ $paginator->total() }}</p>
        <ul class="flex flex-wrap items-center gap-1">
            @if ($paginator->onFirstPage())
                <li><span class="rounded-lg px-3 py-2 text-sm text-stone-400" aria-disabled="true">Previous</span></li>
            @else
                <li><a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="rounded-lg px-3 py-2 text-sm font-medium text-ink hover:bg-brand-50">Previous</a></li>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="px-2 py-2 text-sm text-muted">{{ $element }}</span></li>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li><span aria-current="page" class="rounded-lg bg-brand-600 px-3 py-2 text-sm font-semibold text-white">{{ $page }}</span></li>
                        @else
                            <li><a href="{{ $url }}" aria-label="Go to page {{ $page }}" class="rounded-lg px-3 py-2 text-sm font-medium text-ink hover:bg-brand-50">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <li><a href="{{ $paginator->nextPageUrl() }}" rel="next" class="rounded-lg px-3 py-2 text-sm font-medium text-ink hover:bg-brand-50">Next</a></li>
            @else
                <li><span class="rounded-lg px-3 py-2 text-sm text-stone-400" aria-disabled="true">Next</span></li>
            @endif
        </ul>
    </nav>
@endif