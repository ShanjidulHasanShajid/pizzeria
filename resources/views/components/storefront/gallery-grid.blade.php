@props(['images'])
<section class="page-container py-10" x-data="{ open: false, src: '', alt: '' }" x-on:keydown.escape.window="open = false">
    <x-storefront.section-heading title="Gallery" />
    <ul class="mt-5 grid grid-cols-2 gap-3 md:grid-cols-3">
        @foreach ($images as $image)
            <li>
                <button type="button" class="block aspect-square w-full overflow-hidden rounded-card"
                    aria-label="Open photo: {{ $image['alt'] }}"
                    x-on:click="open = true; src = @js($image['src']); alt = @js($image['alt'])">
                    <img src="{{ $image['src'] }}" alt="{{ $image['alt'] }}" loading="lazy" class="h-full w-full object-cover transition duration-300 hover:scale-105">
                </button>
            </li>
        @endforeach
    </ul>

    <div x-show="open" x-cloak x-transition.opacity x-trap.noscroll="open"
        class="fixed inset-0 z-50 flex items-center justify-center bg-ink/90 p-4"
        role="dialog" aria-modal="true" aria-label="Photo viewer" x-on:click.self="open = false">
        <button type="button" x-on:click="open = false" aria-label="Close photo viewer" class="absolute right-4 top-4 text-white hover:text-cheese">
            <x-icon name="x-mark" class="h-8 w-8" />
        </button>
        <img x-bind:src="src" x-bind:alt="alt" class="max-h-full max-w-full rounded-card">
    </div>
</section>