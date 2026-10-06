@props(['banners'])
@php
    $tones = [
        'brand' => 'bg-brand-600 text-white',
        'accent' => 'bg-accent-700 text-white',
    ];
@endphp
<section class="page-container py-8" aria-label="Promotions">
    <ul class="grid gap-4 md:grid-cols-2">
        @foreach ($banners as $banner)
            <li class="flex items-center justify-between gap-4 rounded-card p-6 {{ $tones[$banner['tone']] ?? $tones['brand'] }}">
                <div>
                    <h2 class="font-display text-2xl font-bold">{{ $banner['title'] }}</h2>
                    <p class="mt-1 text-white/90">{{ $banner['text'] }}</p>
                </div>
                <a href="{{ $banner['url'] }}" class="btn btn-md shrink-0 bg-white text-ink hover:bg-cream">{{ $banner['cta'] }}</a>
            </li>
        @endforeach
    </ul>
</section>