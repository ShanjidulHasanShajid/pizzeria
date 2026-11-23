{{-- Each banner: title, image, url (null for a banner without a link). --}}
@props(['banners'])
<section class="page-container py-8" aria-label="Promotions">
    <ul class="grid gap-4 md:grid-cols-2">
        @foreach ($banners as $banner)
            <li class="group relative flex min-h-48 items-end overflow-hidden rounded-card bg-ink focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-brand-600">
                <img src="{{ $banner['image'] }}" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover transition duration-300 group-hover:scale-105">
                <div class="absolute inset-0 bg-gradient-to-t from-ink/80 to-transparent" aria-hidden="true"></div>
                <h2 class="relative p-6 font-display text-2xl font-bold text-white">
                    @if ($banner['url'])
                        <a href="{{ $banner['url'] }}" class="after:absolute after:inset-0">{{ $banner['title'] }}</a>
                    @else
                        {{ $banner['title'] }}
                    @endif
                </h2>
            </li>
        @endforeach
    </ul>
</section>
