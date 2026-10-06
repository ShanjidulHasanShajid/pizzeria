@props(['badges'])
<section class="border-y border-line bg-white" aria-label="Why order from us">
    <ul class="page-container grid grid-cols-2 gap-4 py-6 md:grid-cols-4">
        @foreach ($badges as $badge)
            <li class="flex items-center gap-3">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-700">
                    <x-icon :name="$badge['icon']" class="h-6 w-6" />
                </span>
                <span>
                    <span class="block text-sm font-semibold text-ink">{{ $badge['title'] }}</span>
                    <span class="block text-xs text-muted">{{ $badge['text'] }}</span>
                </span>
            </li>
        @endforeach
    </ul>
</section>